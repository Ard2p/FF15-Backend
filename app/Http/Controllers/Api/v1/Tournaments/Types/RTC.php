<?php

namespace App\Http\Controllers\Api\v1\Tournaments\Types;

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

use App\Models\TStatistics;
use App\Models\TPlayers;
use App\Models\Tournament;
use App\Models\Logging;
use App\Models\GameAccount;
use App\Models\GameProfile;
use App\Models\TGrids;

class RTC
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

		if ($profile->getRawOriginal('mmr') < 1300)
			return response()->json(['success' => false, 'code' => 'tournament.no_minimal_rang'])->send();

		$filtered = $tournaments_request->where('type', 'rtc')->count();
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
		$generateKeys = true;

		$select = [
			'games_profiles.user_id', 'tournaments_players.id',
			'roles', 'mmr', 'priority', 'team', 'profileId',
		];

		$players = TPlayers::select($select)
			->join('games_accounts', 'games_accounts.id',       '=', 'tournaments_players.account_id')
			->join('games_profiles', 'games_profiles.user_id',  '=', 'tournaments_players.user_id')
			->where('tournaments_players.tournament_id', $tournament->id)
			->where('tournaments_players.round', $tournament->round)->get()->keyBy('user_id');

		if (count($players) >= 20) {
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

			$newPlayers = [];
			$reservePlayers = [];
			foreach ($players as $player) {
				$newPlayers[$player->user_id] = [
					'id' 				=> $player->id,
					'grid_id' 	=> NULL,
					'team' 			=> NULL,
					'role' 			=> 'reserve'
				];
				$profileIds[$player->user_id] = [
					'profileId'	=> $player->profileId
				];
			}

			return DB::transaction(
				function () use ($tournament, $formedTeams, $newPlayers, $profileIds, $reservePlayers, $generateKeys) {
					$tournament->grids()
						->where('tournament_id', $tournament->id)
						->where('round', $tournament->round)->delete();

					$matchs = [];
					for ($i = 0; $i < count($formedTeams[0]); $i += 2) {

						$grid = $tournament->grids()->create([
							'round' => $tournament->round,
							'grid' 	=> 'main',
							'bo' 		=> 1,
							'team1'	=> $i + 1,
							'team2'	=> $i + 2
						]);

						$allowedSummonerIds = config('games.lol.allowedSummonerIds');

						foreach ($formedTeams[0][$i]->getUsers() as $role => $user) {
							unset($reservePlayers[$user->getUserIdentifier()]);
							$newPlayers[$user->getUserIdentifier()]['team'] 		= $i + 1;
							$newPlayers[$user->getUserIdentifier()]['role'] 		= $role;
							$newPlayers[$user->getUserIdentifier()]['grid_id']	= $grid->id;
							$allowedSummonerIds[] = $profileIds[$user->getUserIdentifier()]['profileId'];
						}
						foreach ($formedTeams[0][$i + 1]->getUsers() as $role => $user) {
							unset($reservePlayers[$user->getUserIdentifier()]);
							$newPlayers[$user->getUserIdentifier()]['team']			= $i + 2;
							$newPlayers[$user->getUserIdentifier()]['role'] 		= $role;
							$newPlayers[$user->getUserIdentifier()]['grid_id']	= $grid->id;
							$allowedSummonerIds[] = $profileIds[$user->getUserIdentifier()]['profileId'];
						}

						$riot = new LeagueAPI(config('games.lol.api_config'));

						if ($generateKeys && $tournament->provider_id == null) {
							$tournamentParams = new Objects\TournamentRegistrationParameters([
								'providerId' => config('games.lol.provider'),
								'name'       => "FF15"
							]);
							$provider_id = $riot->createTournament($tournamentParams);
							$tournament->update(['provider_id' => $provider_id]);
						}

						$code = null;
						if ($tournament->provider_id != null) {
							$tournamentParams = new Objects\TournamentCodeParameters([
								'mapType'       		=> 'SUMMONERS_RIFT',
								'pickType'      		=> 'TOURNAMENT_DRAFT',
								'spectatorType' 		=> 'ALL',
								'allowedSummonerIds' => $allowedSummonerIds,
								'teamSize'      		=> 5,
								'metadata'      		=> json_encode([
									'yek2'          	=> config('games.lol.token'),
									'tournament_id' 	=> $tournament->id
								]),
							]);
							$code = $riot->createTournamentCodes($tournament->provider_id, 1, $tournamentParams)[0];
						}

						$matchs[] = [
							'tournament_id'	=> $tournament->id,
							'grid_id'				=> $grid->id,
							'status'				=> 'wait',
							'code'					=> $code ? $code : null
						];
					}

					$tournament->matches()->createMany($matchs);
					TPlayers::upsert($newPlayers, ['id'], ['team', 'role', 'grid_id']);

					Logging::insert(['name' => $tournament->name, 'type' => 'tournament', 'log' => json_encode($formedTeams)]);

					return true;
				}
			);
		}
		return false;
	}

	public function show(Tournament $tournament, Request $request)
	{
		$user = \Auth::user();

		$round = $request->get('round') ? $request->get('round') : $tournament->round;

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

		if ($request->get('round') != 'all') {
			$grids->where('round', $round);
			$players->where('tournaments_players.round', $round);
		}

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
					$grid->matches->makeVisible(['code']);
				});
			} else if($user) {
				$player = $players->where('user_id', $user->id)
					->where('round', $tournament->round)->first();
				if ($player && $player->grid_id) {
					$grid = $grids->where('id', $player->grid_id)->first();
					if ($grid) $grid->matches->makeVisible(['code']);
				}
			}
		}

		$tournament->players = $players;
		$tournament->grids = $grids;

		return response()->json($tournament)->send();
	}
}
