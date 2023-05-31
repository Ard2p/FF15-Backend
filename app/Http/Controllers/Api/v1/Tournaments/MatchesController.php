<?php

namespace App\Http\Controllers\Api\v1\Tournaments;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;

use Carbon\Carbon;
use RiotAPI\LeagueAPI\LeagueAPI;

use App\Models\Tournament;
use App\Models\TStatistics;
use App\Models\TPlayers;
use App\Models\TMatches;
use App\Models\TGrids;
use App\Models\Logging;
use App\Models\GameAccount;
use App\Models\GameProfile;

use App\Jobs\TournamentEvents;

class MatchesController extends Controller
{
	public function check()
	{
		\Config::set('app.debug', true);

		$game = 'lol';
		$type = 'rtc';

		$TMatches = TMatches::select([
			'tournaments_matches.id', 'grid_id',
			'tournaments_matches.status', 'code'
		])
			->join('tournaments', function ($join) use ($game, $type) {
				$join->where('game', $game);
				$join->where('type', $type);
				// $join->where('tournaments.id', 77);
				$join->on('tournaments.id',	'=', 'tournaments_matches.tournament_id');
			})
			// ->where('created_at', 'LIKE', '%' . $date . '%')
			->whereIn('tournaments_matches.status', ['wait', 'lobby', 'ready', 'pick'])->get();

		// TournamentEvents::dispatch('dd')->onQueue('events');


		$promises = [];
		foreach ($TMatches as $TMatch) {
			$promises[$TMatch->id] = new Promise(function () use (&$promises, $TMatch, &$fff) {
				$this->getLobbyData($TMatch);
				// TournamentEvents::dispatch(json_encode($fff[$TMatch->id]))->onQueue('jso1n_encode');
				$promises[$TMatch->id]->resolve('end');
			});
		}

		\GuzzleHttp\Promise\all($promises)->wait();
	}

	function getLobbyData($TMatch)
	{
		$riot = new LeagueAPI(config('games.lol.api_config'));
		$lobbyEvents = $riot->getTournamentLobbyEvents($TMatch->code)->eventList;

		uasort($lobbyEvents, function ($a, $b) {
			return ($a->timestamp > $b->timestamp ? 1 : -1);
		});

		$status = 'wait';
		$players = [];
		foreach ($lobbyEvents as $event) {
			switch ($event->eventType) {
				case 'PracticeGameCreatedEvent':
					$status = 'lobby';
					$players[$event->summonerId] = 'join';
					break;
				case 'PlayerJoinedGameEvent':
					$players[$event->summonerId] = 'join';
					break;
				case 'PlayerQuitGameEvent':
					$players[$event->summonerId] = 'leave';
					break;
				case 'ChampSelectStartedEvent':
					$status = 'pick';
					break;
				case 'GameAllocatedToLsmEvent':
					$status = 'process';
					break;
			}
		}

		$TPlayers = TPlayers::select([
			'tournaments_players.id', 'team', 'profileId'
		])
			->join('games_accounts', function ($join) {
				$join->on('tournaments_players.user_id',	'=', 'games_accounts.user_id');
			})
			->where('grid_id', 		 $TMatch->grid_id)
			->whereIn('profileId', array_keys($players))->get();

		$teamsPlayers = [];
		$TPlayersUpdate = [];
		foreach ($TPlayers as $TPlayer) {
			$playerStatus = $players[$TPlayer->profileId];

			// if ($playerStatus == 'join') $teamsPlayers[$TPlayer->team]++;

			$TPlayersUpdate[] = [
				'id' 		 => $TPlayer->id,
				'status' => $playerStatus
			];
		}

		TMatches::where('id', $TMatch->id)->update(['status' => $status]);
		TPlayers::upsert($TPlayersUpdate, ['id'], ['status']);
	}

	public function getUpdateList()
	{
		\Config::set('app.debug', true);

		// $TMatches = TMatches::select([
		// 	'tournaments_matches.id', 'grid_id',
		// 	'tournaments_matches.status', 'code',
		// 	'tournament_id', 'type', 'game'
		// ])
		// 	->with('players', function ($query) {
		// 		$query->select(['tournaments_players.id', 'grid_id', 'status', 'profileId']);
		// 		$query->join('games_accounts', function ($join) {
		// 			$join->on('games_accounts.id',	'=', 'tournaments_players.account_id');
		// 		});
		// 	})
		// 	->join('tournaments', function ($join) {
		// 		$join->on('tournaments.id',	'=', 'tournaments_matches.tournament_id');
		// 	})
		// 	->whereIn('tournaments_matches.status', ['wait', 'lobby', 'ready', 'pick', 'process'])
		// 	->get();

		// $TPlayersSelect = ['tournaments_players.id', 'team_id', 'role', 'team', 'mmr'];

		// $teams = TPlayers::select($TPlayersSelect)
		// 	->join('teams',	'teams.id',	'=', 'tournaments_players.team_id')
		// 	->join('tournaments',	'tournaments.id',	'=', 'tournaments_players.tournament_id')
		// 	->whereIn('tournaments.status', ['balance', 'process'])

		// 	->with([
		// 		'members' => function ($query) use ($tournament) {
		// 			$query->select(['teams_members.team_id', 'teams_members.user_id', 'profileId']);
		// 			$query->join('games_accounts',	'games_accounts.user_id',	'=', 'teams_members.user_id');
		// 			$query->where('game',	$tournament->game);
		// 			$query->where('active',	true);
		// 		}
		// 	])->get();

		$TMatches = TMatches::select([
			'tournaments_matches.id', 'grid_id',
			'tournaments_matches.status', 'code',
			'tournament_id', 'type', 'game'
		])
			->with('players', function ($query) {
				$query->select(['tournaments_players.id', 'grid_id', 'status', 'profileId']);
				$query->join('games_accounts', function ($join) {
					$join->on('games_accounts.id',	'=', 'tournaments_players.account_id');
				});
			})
			->join('tournaments', function ($join) {
				$join->on('tournaments.id',	'=', 'tournaments_matches.tournament_id');
			})
			->whereIn('tournaments_matches.status', ['wait', 'lobby', 'ready', 'pick', 'process'])
			->get();

		return $TMatches->makeVisible(['code']);
	}

	public function saveStatus(Request $request)
	{
		\Config::set('app.debug', true);
		$data = $request->get('data');

		// if ($data['players'])
		// 	TPlayers::upsert($data['players'], ['id'], ['status']);

		if ($data['matchs'])
			TMatches::upsert($data['matchs'], ['code'], ['status', 'start_at']);

		return response()->json(['success' => true], 200);
	}
}
