<?php

namespace App\Http\Controllers\Api\v1\Tournaments\Types;

use App\Http\Controllers\Api\v1\Tournaments\Games;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

use RiotAPI\LeagueAPI\LeagueAPI;
use RiotAPI\LeagueAPI\Objects;

use App\Vendors\balance\Service\RolesBalancer;
use App\Vendors\balance\Service\TeamsFormer;
use App\Vendors\balance\Entity\UsersByRole;
use App\Vendors\balance\Entity\Role;
use App\Vendors\balance\Entity\User;

use App\Models\TGrids;
use App\Models\TMatches;
use App\Models\TPlayers;
use App\Models\Tournament;
use App\Models\GameAccount;
use App\Models\GameProfile;
use App\Models\Team as TeamDB;
use App\Models\TeamMember;

class TEAM_DE extends Type
{
	public function enter(Tournament $tournament, $tournaments_request)
	{
		$user = \Auth::user();

		$team = TeamDB::select(['teams.id', 'teams.mmr', 'teams.status', 'teams_members.status as member_status'])
			->join('teams_members', 'teams.id', '=', 'teams_members.team_id')
			->where('teams_members.user_id', $user->id)
			->where('teams.game', $tournament->game)
			->first();

		if (!$team)
			return response()->json(['success' => false, 'code' => 'team.not_found'])->send();

		if ($team->member_status !== 'owner')
			return response()->json(['success' => false, 'code' => 'team.you_not_owner'])->send();

		if ($team->members()->count() < 5)
			return response()->json(['success' => false, 'code' => 'team.not_enough_members'])->send();

		$player = TPlayers::where('tournament_id', $tournament->id)
			->where('team_id', $team->id)->first();
		if ($player)
			return response()->json(['success' => false, 'code' => 'tournament.enter_already'])->send();

		// if ($profile->getRawOriginal('mmr') < 1300)
		// 	return response()->json(['success' => false, 'code' => 'tournament.no_minimal_rang'])->send();

		// $filtered = $tournaments_request->where('type', 'team')->count();
		// if ($filtered > 0)
		// 	return response()->json(['success' => false, 'code' => 'tournament.more_type_request'])->send();

		$newPlayer = ['team_id' => $team->id];
		$enter = $tournament->players()->create($newPlayer);
		if ($enter) return true;
		else return false;
	}

	public function leave(Tournament $tournament)
	{
		$user = \Auth::user();

		$team = TeamDB::select(['teams.id', 'teams_members.status as member_status'])
			->join('teams_members', 'teams.id', '=', 'teams_members.team_id')
			->where('teams_members.user_id', $user->id)
			->where('teams.game', $tournament->game)
			->first();

		if (!$team)
			return response()->json(['success' => false, 'code' => 'team.not_found'])->send();

		if ($team->member_status !== 'owner')
			return response()->json(['success' => false, 'code' => 'team.you_not_owner'])->send();

		$player = TPlayers::where('team_id', $team->id)
			->where('tournament_id', $tournament->id)->first();
		if (!$player)
			return response()->json(['success' => false, 'code' => 'tournament.not_registred'])->send();

		if ($player->team != null)
			return response()->json(['success' => false, 'code' => 'tournament.player_in_game'])->send();

		$player->delete();

		$select = [
			'tournaments.id AS tournament_id', 'tournaments.round',
			'tournaments.type', 'name', 'tournaments.status', 'start', 'game',
			'tournaments_players.user_id', 'team_id'
		];

		$tournaments_request = TPlayers::select($select)
			->join('tournaments', function ($join) {
				$join->on('tournaments.id', 		'=', 'tournaments_players.tournament_id');
				$join->on('tournaments.round', 	'=', 'tournaments_players.round');
			})
			->where(function ($query) use ($user, $team) {
				$query->where('tournaments_players.user_id', $user->id);
				if ($user->team)
					$query->orWhere('tournaments_players.team_id', $team->id);
			})->whereIn('tournaments.status', ['open', 'balance', 'process'])->get();

		return response()->json(['success' => true, 'tournaments_request' => $tournaments_request])->send();
	}

	public function balance(Tournament $tournament)
	{
		$GameClass = $this->getGameClass($tournament->game);

		// TODO: Проверка на минимум 4 команды

		$TPlayersSelect = [
			'tournaments_players.id', 'team_id', 'role', 'team', 'mmr'
		];

		$teams = TPlayers::select($TPlayersSelect)
			->where('tournaments_players.tournament_id', 	$tournament->id)
			->join('teams',	'teams.id',	'=', 'tournaments_players.team_id')
			->with([
				'members' => function ($query) use ($tournament) {
					$query->select(['teams_members.team_id', 'teams_members.user_id', 'profileId']);
					$query->join('games_accounts',	'games_accounts.user_id',	'=', 'teams_members.user_id');
					$query->where('game',	$tournament->game);
					$query->where('active',	true);
				}
			])->get();

		$grids = [];
		$grids_loosers = [];
		$summonerIds = [];
		$teamsUpdate = [];

		$games = 0;
		$rounds = (int)ceil(log(count($teams), 2));
		$gamesFirstRound = count($teams) - pow(2, $rounds - 1);


		// ОСНОВНАЯ СЕТКА
		$offset = 0;
		for ($roundNum = 1; $roundNum <= $rounds; $roundNum++) {
			$games = pow(2, $rounds - $roundNum);

			for ($gameNum = 1; $gameNum <= $games; $gameNum++) {
				if (!isset($grids[$gameNum + $offset])) {

					$bo = 1;
					// if ($games == 8)
					// 	$bo = 3;
					// if ($games == 4)
					// 	$bo = 5;
					// if ($games == 2)
					// 	$bo = 3;
					// if ($games == 1)
					// 	$bo = 5;

					$grids[$gameNum + $offset] = [
						'tournament_id' => $tournament->id,
						'num' 	=> $gameNum + $offset,
						'round' => $roundNum,
						'grid' 	=> 'main',
						'bo' 		=> $bo,
						'win' 	=> NULL,
						'team1' => NULL,
						'team2' => NULL,
						'team1_score' => 0
					];
				}

				if ($roundNum == 1) {
					if ($gamesFirstRound >= $gameNum) {
						$grids[$gameNum]['team1'] = $gameNum;
						$grids[$gameNum]['team2'] = $gameNum + $games;

						$TPlayerId = $teams[$grids[$gameNum]['team1'] - 1]->id;
						$summonerIds[$grids[$gameNum]['team1']] = $teams[$grids[$gameNum]['team1'] - 1]->members->pluck('profileId')->toArray();;
						$teamsUpdate[] = [
							'id' 		=> $TPlayerId,
							'team' 	=> $grids[$gameNum]['team1']
						];

						$TPlayerId = $teams[$grids[$gameNum]['team2'] - 1]->id;
						$summonerIds[$grids[$gameNum]['team2']] = $teams[$grids[$gameNum]['team2'] - 1]->members->pluck('profileId')->toArray();;
						$teamsUpdate[] = [
							'id' 		=> $TPlayerId,
							'team' 	=> $grids[$gameNum]['team2']
						];
					} else {
						$grids[$gameNum]['team1_score']	= ceil($grids[$gameNum]['bo'] / 2);
						$grids[$gameNum]['team1'] 			= $gameNum;
						$grids[$gameNum]['win'] 				= $grids[$gameNum]['team1'];

						$TPlayerId = $teams[$grids[$gameNum]['team1'] - 1]->id;
						$summonerIds[$grids[$gameNum]['team1']] = $teams[$grids[$gameNum]['team1'] - 1]->members->pluck('profileId')->toArray();
						$teamsUpdate[] = [
							'id' 		=> $TPlayerId,
							'team' 	=> $grids[$gameNum]['team1']
						];

						$nextNum = (int)ceil($grids[$gameNum]['team1'] / 2) + $games;
						$team = $grids[$gameNum]['team1'] % 2 == 0 ? 'team2' : 'team1';

						if (!isset($grids[$nextNum])) {
							$bo = 1;
							$grids[$nextNum] = [
								'tournament_id' => $tournament->id,
								'num' 	=> $nextNum,
								'round' => $roundNum + 1,
								'grid' 	=> 'main',
								'bo' 		=> $bo,
								'win' 	=> NULL,
								'team1' => NULL,
								'team2' => NULL,
								'team1_score' => 0
							];
						}
						$grids[$nextNum][$team] = $grids[$gameNum]['team1'];
					}
				}
			}
			$offset += $games;
		}

		ksort($grids);

		// ОСНОВНАЯ СЕТКА



		// Добавочный раунд

		$bo = 3;
		$grids[$offset + 1] = [
			'tournament_id' => $tournament->id,
			'num' 	=> $offset + 1,
			'round' => $rounds + 1,
			'grid' 	=> 'main',
			'bo' 		=> $bo,
			'win' 	=> NULL,
			'team1' => NULL,
			'team2' => NULL,
			'team1_score' => 0
		];

		// Добавочный раунд

		// СЕТКА ЛУЗЕРОВ

		$offset = 0;
		for ($roundNum = 1; $roundNum <= ($rounds - 1) * 2; $roundNum += 2) {
			$games = pow(2, $rounds - 1 - ceil($roundNum / 2));

			for ($i = 0; $i <= 1; $i++) {
				for ($gameNum = 1; $gameNum <= $games; $gameNum++) {
					$num = $gameNum + $offset;
					$bo = 1;
					$grids_loosers[] = [
						'tournament_id' => $tournament->id,
						'num' 	=> $num,
						'round' => $roundNum + $i,
						'grid' 	=> 'loosers',
						'bo' 		=> $bo
					];
				}
				$offset += $games;
			}
		}

		//СЕТКА ЛУЗЕРОВ

		return DB::transaction(
			function () use ($tournament, $teamsUpdate, $grids, $grids_loosers, $summonerIds, $GameClass) {

				TGrids::where('tournament_id', $tournament->id)->delete();
				TMatches::where('tournament_id', $tournament->id)->delete();

				TGrids::insert($grids);
				TGrids::insert($grids_loosers);
				TPlayers::upsert($teamsUpdate, ['id'], ['team']);

				$matches = [];
				$grids = $tournament->grids()->select('*')->get();
				$grids->each(function ($grid, $k) use (&$matches, $tournament, $summonerIds, $GameClass) {

					for ($i = 1; $i <= $grid->bo; $i++) {

						$status = 'reserve';
						$code = NULL;
						$win = NULL;

						if ($grid->grid == 'main' && $grid->round == 1 && $grid->team2 == NULL && $grid->win != NULL && $i <= (int)ceil($grid->bo / 2)) {
							$status = 'technical';
							$win = $grid->win;
						}

						if ($grid->team1 && $grid->team2) {
							if (method_exists($GameClass, 'generateCode')) {
								$status = 'wait';
								$allowedSummonerIds = array_merge($summonerIds[$grid->team1], $summonerIds[$grid->team2]);
								$code = $GameClass->generateCode($tournament, ['allowedSummonerIds' => $allowedSummonerIds]);
							}
						}

						$matches[] = [
							'tournament_id' => $tournament->id,
							'grid_id' 			=> $grid->id,
							'status'				=> $status,
							'code'					=> $code,
							'win' 					=> $win
						];
					}
				});

				TMatches::insert($matches);
				return true;
			}
		);
	}

	public function show(Tournament $tournament, Request $request)
	{
		// $user = \Auth::user();

		// $team = TeamDB::select('name')->join('teams_members', function ($join) {
		// 	$join->on('teams.id', '=', 'teams_members.team_id');
		// })->where('game', $tournament->game)
		// 	->where('teams_members.user_id', $user->id);

		$grids = TGrids::select('*')
			->where('tournament_id', $tournament->id)
			->with(['matches' => function ($query) {
				$query->select(['id', 'grid_id', 'status', 'win', 'code', 'start_at', 'end_at']);
			}]);

		$TPlayersSelect = [
			'tournaments_players.team_id', 'tournaments_players.role', 'tournaments_players.status',
			'team', 'avatar', 'name', 'tag', 'mmr', 'teams.status AS team_status', 'teams.id AS team_id'
		];

		// if (\Perm::allows('tournament@info-role'))
		// 	if (\Perm::role(['moder', 'admin', 'dev']))
		// 		$TPlayersSelect = array_merge($TPlayersSelect, ['note']);

		$players = TPlayers::select($TPlayersSelect)
			->where('tournaments_players.tournament_id', 	$tournament->id)
			->join('teams',	'teams.id',	'=', 'tournaments_players.team_id')
			->with([
				'members' => function ($query) use ($tournament) {
					$membersSelects = [
						'teams_members.team_id', 'teams_members.user_id',
						'nickname', 'avatar', /*'champions',*/ 'exp', 'note', 'role'
					];

					$query->select($membersSelects);
					$query->join('users',	'users.id',	'=', 'teams_members.user_id');
					$query->join('games_accounts',	'games_accounts.user_id',	'=', 'teams_members.user_id');

					$query->where('game', $tournament->game);
					$query->where('active', true);
				}

				// 	'profile' => function ($query) use ($tournament) {
				// 		$profileSelects = ['user_id', 'game', 'mmr'];

				// 		if (\Perm::allows('tournament@info-role'))
				// 			if (\Perm::role(['moder', 'admin', 'dev']))
				// 				$profileSelects = array_merge($profileSelects, ['priority', 'roles', 'mmr as elo', 'champions']);

				// 		$query->select($profileSelects);
				// 		$query->where('game', $tournament->game);
				// 	},
				// 	'statistics' => function ($query) use ($tournament) {
				// 		$query->select('user_id', 'win', 'lose');
				// 		$query->where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), date('Y-m'));
				// 		$query->where('game', $tournament->game);
				// 		$query->where('type',	$tournament->type);
				// 	}
			]);

		$players = $players->get();

		if ($tournament->grid_disable) {
			$grids = [];
			$players = $players->map(function ($player, $key) {
				$player->team = null;
				$player->role = null;
				return $player;
			});
		} else {
			$grids = $grids->get();

			$grids->each(function ($grid, $k) {
				$grid->matches->makeVisible(['code']);
			});

			// if (\Perm::allows('tournament@edit', $tournament)) {
			// 	$grids->each(function ($grid, $k) {
			// 		$grid->matches->makeVisible(['code']);
			// 	});
			// } else {
			// 	$player = $players->where('team_id', $team->id)->first();
			// 	if ($player && $player->team) {
			// 		$grids_team = $grids->where('team1', $player->team)->orWhere('team2', $player->team)->get();
			// 		// if ($grids_team)
			// 		$grids_team->each(function ($grid, $k) {
			// 			$grid->matches->makeVisible(['code']);
			// 		});
			// 	}
			// }
		}

		$tournament->teams = $players;
		$tournament->grids = $grids;

		return response()->json($tournament)->send();
	}

	public function matchCheck($grid)
	{
		//Продвижение по сетке
		if ($grid->win) {

			#region Подготовка переменных
			$teams = [];
			$GameClass = $this->getGameClass($grid->tournament->game);

			if (!isset($teams[$grid->tournament->id])) {
				$playersCount = TPlayers::where('tournament_id', $grid->tournament->id)->count();
				$rounds = (int)ceil(log($playersCount, 2));

				$teams[$grid->tournament->id] = [
					'teamsCount' 	=> $playersCount,
					'roundsCount' => $rounds,
					'gamesRounds' => [],
					'gamesLoosers' => []
				];

				for ($roundNum = 1; $roundNum <= $rounds; $roundNum++) {
					$games = (int)pow(2, $rounds - $roundNum);
					$teams[$grid->tournament->id]['gamesRounds'][$roundNum] = $games;
				}
				for ($roundNum = 1; $roundNum <= ($rounds - 1) * 2; $roundNum++) {
					$games = (int)pow(2, $rounds - 1 - ceil($roundNum / 2));
					$teams[$grid->tournament->id]['gamesLoosers'][$roundNum] = $games;
				}
			}
			$tData = $teams[$grid->tournament->id];
			#endregion


			#region Топ сетка продвижение
			if ($grid->grid == 'main' && $grid->round < $tData['roundsCount'] + 1) {

				#region Для побидителя
				$offset = 0;
				if ($grid->round != 1)
					for ($i = 1; $i < $grid->round; $i++)
						$offset += $tData['gamesRounds'][$i];

				$gamesRound = $tData['gamesRounds'][$grid->round];
				$num 		 		= $grid->num - $offset;
				$nextNum 		= (int)ceil($num / 2) + $offset + $gamesRound;
				$team 			= $num % 2 == 0 ? 'team2' : 'team1';

				$gMain[$team] = $grid->win;

				TGrids::where('tournament_id', $grid->tournament_id)
					->where('grid', 'main')
					->where('num', $nextNum)
					->update($gMain);

				$GameClass->nextMatchCode($grid->tournament, $nextNum, 'main');
				#endregion


				#region Для проигравшего
				$offset = 0;
				if ($grid->round == 1) {
					$nextLooseNum = (int)ceil($num / 2);
					if ($grid->win == $grid->team1)
						$gLoosers[$team] = $grid->team2;
					else
						$gLoosers[$team] = $grid->team1;
				} else {

					if ($grid->round == 2) {
						$offset += $tData['gamesRounds'][$i];
					} else {
						for ($i = 2; $i < $grid->round; $i++)
							$offset += $tData['gamesRounds'][$i];
						$offset *= 2;
						$offset += $tData['gamesRounds'][$grid->round];
					}

					$nextLooseNum = $num + $offset;
					if ($grid->win == $grid->team1)
						$gLoosers['team2'] = $grid->team2;
					else
						$gLoosers['team2'] = $grid->team1;
				}

				TGrids::where('tournament_id', $grid->tournament_id)
					->where('grid', 'loosers')
					->where('num', $nextLooseNum)
					->update($gLoosers);

				$GameClass->nextMatchCode($grid->tournament, $nextLooseNum, 'loosers');
				#endregion


			}
			#endregion


			#region Луз сетка продвижение
			$roundsLoose = ($tData['roundsCount']  - 1) * 2;
			if ($grid->grid == 'loosers' && $grid->round <= $roundsLoose) {

				if ($grid->round == $roundsLoose) {
					$offset = 0;

					for ($i = 1; $i <= $tData['roundsCount']; $i++)
						$offset += $tData['gamesRounds'][$i];
					$numNext = ++$offset;

					$gReturnMain['team2'] = $grid->win;

					TGrids::where('tournament_id', $grid->tournament_id)
						->where('grid', 'main')
						->where('num', $numNext)
						->update($gReturnMain);

					$GameClass->nextMatchCode($grid->tournament, $numNext, 'main');
				} else {
					$offset = 0;

					for ($i = 1; $i < $grid->round; $i++)
						$offset += $tData['gamesLoosers'][$i];

					$num = $grid->num - $offset;

					if ($grid->round < $roundsLoose)
						$numNext = $tData['gamesLoosers'][$grid->round];

					if ($grid->round % 2 == 0) {
						$team 	 = $num % 2 == 0 ? 'team2' : 'team1';
						$numNext += (int)ceil($num / 2) + $offset;

						$gLoosers[$team] = $grid->win;
					} else {
						$numNext += $num + $offset;
						$gLoosers['team1'] = $grid->win;
					}

					TGrids::where('tournament_id', $grid->tournament_id)
						->where('grid', 'loosers')
						->where('num', $numNext)
						->update($gLoosers);

					$GameClass->nextMatchCode($grid->tournament, $numNext, 'loosers');
				}
			}
			#endregion
		}
	}
}
