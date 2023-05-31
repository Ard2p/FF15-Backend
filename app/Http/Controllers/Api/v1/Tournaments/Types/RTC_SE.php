<?php

namespace App\Http\Controllers\Api\v1\Tournaments\Types;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


use App\Vendors\balance\Service\RolesBalancer;
use App\Vendors\balance\Service\TeamsFormer;
use App\Vendors\balance\Entity\UsersByRole;
use App\Vendors\balance\Entity\Role;
use App\Vendors\balance\Entity\User;

use App\Models\TPlayers;
use App\Models\Tournament;
use App\Models\Logging;
use App\Models\GameAccount;
use App\Models\GameProfile;
use App\Models\TGrids;
use App\Models\TMatches;

class RTC_SE extends Type
{
	public function enter(Tournament $tournament, $tournaments_request)
	{
		$user = \Auth::user();

		$player = TPlayers::where('tournament_id', $tournament->id)
			->where('round', '=', $tournament->round)
			->where('user_id', $user->id)->first();
		if ($player)
			return response()->json(['success' => false, 'code' => 'tournament.enter_already'])->send();

		$accounts = GameAccount::where('game', $tournament->game)->where('user_id', $user->id)->get();
		if (!$accounts->count())
			return response()->json(['success' => false, 'code' => 'tournament.no_account', 'game' => $tournament->game])->send();

		$profile = GameProfile::where('game', $tournament->game)->where('user_id', $user->id)->first();
		if (!$profile)
			return response()->json([
				'success' => false, 'code' => 'tournament.no_profile',
				'game' => $tournament->game, 'league' => config('games.lol.leagues')
			])->send();

		$account = $accounts->where('active', true)->first();
		if (!$account)
			return response()->json(['success' => false, 'code' => 'tournament.no_account_active', 'game' => $tournament->game])->send();

		if ($profile->getRawOriginal('mmr') < 900)
			return response()->json(['success' => false, 'code' => 'tournament.no_minimal_rang'])->send();

		$filtered = $tournaments_request->where('type', 'rtc_se')->count();
		if ($filtered > 0)
			return response()->json(['success' => false, 'code' => 'tournament.more_type_request'])->send();

		$newPlayer = ['user_id' => $user->id, 'account_id' => $account->id, 'round' => $tournament->round];
		$enter = $tournament->players()->create($newPlayer);
		if ($enter) return true;
		else return false;
	}

	public function leave(Tournament $tournament)
	{
		$user = \Auth::user();

		$player = TPlayers::where('user_id', $user->id)
			->where('tournament_id', $tournament->id)
			->where('round', $tournament->round)->first();
		if (!$player)
			return response()->json(['success' => false, 'code' => 'tournament.not_registred'])->send();

		if ($player->team != null)
			return response()->json(['success' => false, 'code' => 'tournament.player_in_game'])->send();

		$player->delete();

		$select = ['tournaments.id AS tournament_id', 'name', 'tournaments.type',  'tournaments.status', 'start', 'game'];
		$tournaments_request = TPlayers::select($select)
			->join('tournaments', function ($join) {
				$join->on('tournaments.id', 		'=', 'tournaments_players.tournament_id');
				$join->on('tournaments.round', 	'=', 'tournaments_players.round');
			})
			->where('tournaments_players.user_id', $user->id)
			->whereIn('tournaments.status', ['open', 'balance', 'process'])
			->get();

		return response()->json(['success' => true, 'tournaments_request' => $tournaments_request])->send();
	}

	public function balance(Tournament $tournament)
	{
		#region формирование команд

		$select = [
			'games_profiles.user_id', 'tournaments_players.id',
			'roles', 'mmr', 'priority', 'team', 'profileId',
		];

		$players = TPlayers::select($select)
			->join('games_accounts', 'games_accounts.id',       '=', 'tournaments_players.account_id')
			->join('games_profiles', 'games_profiles.user_id',  '=', 'tournaments_players.user_id')
			->where('tournaments_players.tournament_id', $tournament->id)
			->where('tournaments_players.round', $tournament->round)->get()->keyBy('user_id');

		if (count($players) < 20)
			return response()->json(['success' => false, 'code' => 'tournament.not_enough_players'])->send();

		$roles_list = [
			'sup'   => new Role('sup'),
			'adc'   => new Role('adc'),
			'top'   => new Role('top'),
			'mid'   => new Role('mid'),
			'jung'  => new Role('jung')
		];

		$usersByRoles = [];
		foreach ($roles_list as $role_name => $role)
			$usersByRoles[$role_name] = new UsersByRole($role);

		foreach ($players as $player) {
			if ($player->team == -1) continue;

			$player->roles = json_decode($player->roles);
			$usersByRoles[$player->roles[0]]->addUser(new User($player->getRawOriginal('mmr'), [
				$roles_list[$player->roles[0]],
				$roles_list[$player->roles[1]],
				$roles_list[$player->roles[2]],
				$roles_list[$player->roles[3]],
				$roles_list[$player->roles[4]],
			], $player->user_id, $player->priority));
		}

		$rolesBalancer = new RolesBalancer(count($players), $roles_list);
		$usersByRoles  = $rolesBalancer->getBalancedUsersByRoles($usersByRoles);

		foreach ($usersByRoles as $key => $userByRoles) {
			$collection = collect($userByRoles->getUsers());
			$sorted = $collection->sortByDesc(function ($user, $key) {
				return $user->getPriority();
			})->all();
			while (count($sorted) > (int) (count($players) / 5)) {
				end($sorted);
				unset($sorted[key($sorted)]);
			}
			$usersByRoles[$key]->setUsers($sorted);
		}

		$teamsFormer = new TeamsFormer($usersByRoles);
		$formedTeams = $teamsFormer->formTeams(false);

		#endregion

		#region формирование сетки

		$newPlayers = [];
		foreach ($players as $player) {
			$newPlayers[$player->user_id] = [
				'id'	 => $player->id,
				'team' => NULL,
				'role' => 'reserve'
			];
			$profileIds[$player->user_id] = [
				'profileId'	=> $player->profileId
			];
		}

		// ОСНОВНАЯ СЕТКА

		$grids = [];
		$summonerIds = [];

		$games = 0;
		$rounds = (int)ceil(log(intdiv(count($players), 5), 2));
		$gamesFirstRound = intdiv(count($players), 5) - pow(2, $rounds - 1);

		$offset = 0;
		for ($roundNum = 1; $roundNum <= $rounds; $roundNum++) {
			$games = pow(2, $rounds - $roundNum);

			for ($gameNum = 1; $gameNum <= $games; $gameNum++) {
				if (!isset($grids[$gameNum + $offset])) {

					$bo = 1;
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

						foreach ($formedTeams[0][$grids[$gameNum]['team1'] - 1]->getUsers() as $role => $user) {
							$newPlayers[$user->getUserIdentifier()]['team']	= $grids[$gameNum]['team1'];
							$newPlayers[$user->getUserIdentifier()]['role']	= $role;
							$summonerIds[$grids[$gameNum]['team1']][] 			= $profileIds[$user->getUserIdentifier()]['profileId'];
						}
						foreach ($formedTeams[0][$grids[$gameNum]['team2'] - 1]->getUsers() as $role => $user) {
							$newPlayers[$user->getUserIdentifier()]['team']	= $grids[$gameNum]['team2'];
							$newPlayers[$user->getUserIdentifier()]['role']	= $role;
							$summonerIds[$grids[$gameNum]['team2']][] 			= $profileIds[$user->getUserIdentifier()]['profileId'];
						}
					} else {
						$grids[$gameNum]['team1_score']	= ceil($grids[$gameNum]['bo'] / 2);
						$grids[$gameNum]['team1'] 			= $gameNum;
						$grids[$gameNum]['win'] 				= $grids[$gameNum]['team1'];

						foreach ($formedTeams[0][$grids[$gameNum]['team1'] - 1]->getUsers() as $role => $user) {
							$newPlayers[$user->getUserIdentifier()]['team']	= $grids[$gameNum]['team1'];
							$newPlayers[$user->getUserIdentifier()]['role']	= $role;
							$summonerIds[$grids[$gameNum]['team1']][] 			= $profileIds[$user->getUserIdentifier()]['profileId'];
						}

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

		#endregion

		$GameClass = $this->getGameClass($tournament->game);

		return DB::transaction(
			function () use ($tournament, $newPlayers, $grids, $summonerIds, $GameClass) {

				TGrids::where('tournament_id', $tournament->id)->delete();
				TMatches::where('tournament_id', $tournament->id)->delete();

				TGrids::insert($grids);
				TPlayers::upsert($newPlayers, ['id'], ['team', 'role']);

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

		return false;
	}

	public function show(Tournament $tournament, Request $request)
	{
		// $user = \Auth::user();

		$grids = TGrids::select('*')
			->where('tournament_id', $tournament->id)
			->with(['matches' => function ($query) {
				$query->select(['id', 'grid_id', 'tournament_id', 'status', 'win', 'code']);
			}]);

		$TPlayersSelect = [
			'tournaments_players.user_id', 'tournaments_players.role', 'tournaments_players.status',
			'round', 'team', 'nickname', 'avatar', 'exp', 'users.role AS site_role', 'grid_id'
		];

		if (\Perm::allows('tournament@info-role'))
			if (\Perm::role(['moder', 'admin', 'dev']))
				$TPlayersSelect = array_merge($TPlayersSelect, ['note']);

		$players = TPlayers::select($TPlayersSelect)
			->where('tournaments_players.tournament_id', 	$tournament->id)
			->join('users',						'users.id',						'=', 'tournaments_players.user_id')
			->join('games_accounts',	'games_accounts.id',	'=', 'tournaments_players.account_id')
			->with([
				'profile' => function ($query) use ($tournament) {
					$profileSelects = ['user_id', 'game', 'mmr', 'champions', 'roles'];

					if (\Perm::allows('tournament@info-role'))
						if (\Perm::role(['moder', 'admin', 'dev']))
							$profileSelects = array_merge($profileSelects, ['priority', 'mmr as elo',]);

					$query->select($profileSelects);
					$query->where('game', $tournament->game);
				},
				'member' => function ($query) use ($tournament) {
					$query->select('user_id', 'team_id', 'name', 'tag', 'avatar', 'teams.status AS status', 'teams_members.status AS member');
					$query->join('teams', 'teams.id', '=', 'teams_members.team_id');
					$query->where('game', $tournament->game);
				},
				'statistics' => function ($query) use ($tournament) {
					$query->select('user_id', 'win', 'lose');
					$query->where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), date('Y-m'));
					$query->where('game', $tournament->game);
					$query->where('type',	$tournament->type);
				}
			]);

		$players = $players->get();
		// $name = '_t' . $tournament->id . '_r' . $round . '_u-r' . $user->role;
		// $players = \Cache::remember('TPlayers' . $name, 5, function () use ($players) {
		// 	return $players->get();
		// });

		if ($tournament->grid_disable) {
			$grids = [];
			$players = $players->map(function ($player, $key) {
				$player->team = null;
				$player->role = null;
				return $player;
			});
		} else {
			$grids = $grids->get();
			// $grids = \Cache::remember('TGrids' . $name, 5, function () use ($grids) {
			// 	return $grids->get();
			// });

			if (\Perm::allows('tournament@edit', $tournament)) {
				$grids->each(function ($grid, $k) {
					$grid->matches(function ($match, $k) {
						$match->makeVisible(['code']);
					});
				});
			}

			// else if($user) {
			// 	$player = $players->where('user_id', $user->id)
			// 		->where('round', $tournament->round)->first();
			// 	if ($player && $player->grid_id) {
			// 		$grid = $grids->where('id', $player->grid_id)->first();
			// 		if ($grid) $grid->matches->makeVisible(['code']);
			// 	}
			// }
		}

		$tournament->players = $players;
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
				$playersCount = TPlayers::where('tournament_id', $grid->tournament->id)
					->whereNotNull('team')->count();
				$teamsCount = intdiv($playersCount, 5);
				$rounds = (int)ceil(log($teamsCount, 2));

				$tData = [
					'teamsCount' 	=> $teamsCount,
					'roundsCount' => $rounds,
					'gamesRounds' => [],
					'gamesLoosers' => []
				];

				for ($roundNum = 1; $roundNum <= $rounds; $roundNum++) {
					$games = (int)pow(2, $rounds - $roundNum);
					$tData['gamesRounds'][$roundNum] = $games;
				}
			}
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

				$GameClass->nextMatchCodeRTC($grid->tournament, $nextNum, 'main');
				#endregion
			}
			#endregion
		}
	}
}
