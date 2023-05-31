<?php

namespace App\Http\Controllers\Api\v1\Tournaments\Games;

use Illuminate\Http\Request;
use Carbon\Carbon;

use RiotAPI\LeagueAPI\LeagueAPI;
use RiotAPI\LeagueAPI\Objects;

use App\Models\Logging;

use App\Models\TMatches;
use App\Models\TPlayers;
use App\Models\Tournament;
use App\Models\TGrids;

class LoL
{
	public function callback(Request $request)
	{
		$data = $request->json()->all();
		$metaData = json_decode($data['metaData']);

		if (!$data || !$metaData)
			return response()->json(['success' => false, 'code' => 'callback.data_undefined'], 406)->send();

		if (!isset($metaData->yek2) || !$metaData->yek2 == config('games.lol.token'))
			return response()->json(['success' => false, 'code' => 'callback.mismatch_token'], 401)->send();

		$tMatch = TMatches::where('tournament_id', $metaData->tournament_id)->where('code', $data['shortCode'])->first();
		if (!$tMatch)
			return response()->json(['success' => false, 'code' => 'callback.match_not_found'], 404)->send();

		if (in_array($tMatch->status, ['end', 'success']))
			return response()->json(['success' => true, 'code' => 'callback.already'])->send();

		$tMatch->update(['status'  => 'end', 'match_id'  => $data['region'] . '_' . $data['gameId']]);




		try {
			Logging::insert(['name' => $data['shortCode'], 'type' => 'callback', 'log' => json_encode($data)]);
		} catch (\Exception $e) {}






		return response()->json(['success' => true])->send();
	}

	public function generateCode(Tournament $tournament, $options = [], $generateKeys = true)
	{
		$riot = new LeagueAPI(config('games.lol.api_config'));

		if ($generateKeys && $tournament->provider_id == null) {
			$tournamentParams = new Objects\TournamentRegistrationParameters([
				'providerId' => config('games.lol.provider'),
				'name'       => "FF15"
			]);

			$provider_id = $riot->createTournament($tournamentParams);
			$tournament->update(['provider_id' => $provider_id]);
		}

		if ($tournament->provider_id != null) {

			if (!isset($options['allowedSummonerIds']))
				return response()->json(['success' => false, 'code' => 'tournament.empty_allowed_summoner_ids'])->send();

			$allowedSummonerIds = array_merge(config('games.lol.allowedSummonerIds'), $options['allowedSummonerIds']);
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

			return $riot->createTournamentCodes($tournament->provider_id, 1, $tournamentParams)[0];
		}

		return NULL;
	}

	public function nextMatchCode($tournament, $numNext, $grid)
	{
		$gridNext = TGrids::select(['team1', 'team2', 'id'])
			->where('tournament_id', $tournament->id)
			->where('grid', $grid)
			->where('num', $numNext)->first();

		if ($gridNext->team1 && $gridNext->team2) {

			$current_timestamp = Carbon::now();
			$gridNext->update(['prepare_at' => $current_timestamp]);

			$teams = TPlayers::select(['tournaments_players.team_id'])
				->where('tournaments_players.tournament_id', 	$tournament->id)
				->where(function ($query) use ($gridNext) {
					$query->where('tournaments_players.team', $gridNext->team1);
					$query->orWhere('tournaments_players.team', $gridNext->team2);
				})
				->join('teams',	'teams.id',	'=', 'tournaments_players.team_id')
				->with([
					'members' => function ($query) use ($tournament) {
						$query->select(['teams_members.team_id', 'teams_members.user_id', 'profileId']);
						$query->join('games_accounts',	'games_accounts.user_id',	'=', 'teams_members.user_id');
						$query->where('game',	$tournament->game);
						$query->where('active',	true);
					}
				])->get();

			$allowedSummonerIds = [];
			$teams->each(function ($team, $k) use (&$allowedSummonerIds) {
				$allowedSummonerIds = array_merge($allowedSummonerIds, $team->members->pluck('profileId')->toArray());
			});

			$code = $this->generateCode($tournament, ['allowedSummonerIds' => $allowedSummonerIds]);
			TMatches::where('grid_id', $gridNext->id)->first()->update(['code' => $code, 'status' => 'wait']);
			// TMatches::where('grid_id', $gridNext->id)->where('status', 'reserve')
			// 	->first()->update(['code' => $code, 'status' => 'wait']);
		}
	}

	public function matchCode($tournament, $grid, $match)
	{
		if ($grid->team1 && $grid->team2) {
			$current_timestamp = Carbon::now();
			$grid->update(['prepare_at' => $current_timestamp]);

			$teams = TPlayers::select(['tournaments_players.team_id'])
				->where('tournaments_players.tournament_id', 	$tournament->id)
				->where(function ($query) use ($grid) {
					$query->where('tournaments_players.team', $grid->team1);
					$query->orWhere('tournaments_players.team', $grid->team2);
				})
				->join('teams',	'teams.id',	'=', 'tournaments_players.team_id')
				->with([
					'members' => function ($query) use ($tournament) {
						$query->select(['teams_members.team_id', 'teams_members.user_id', 'profileId']);
						$query->join('games_accounts',	'games_accounts.user_id',	'=', 'teams_members.user_id');
						$query->where('game',	$tournament->game);
						$query->where('active',	true);
					}
				])->get();

			$allowedSummonerIds = [];
			$teams->each(function ($team, $k) use (&$allowedSummonerIds) {
				$allowedSummonerIds = array_merge($allowedSummonerIds, $team->members->pluck('profileId')->toArray());
			});

			$code = $this->generateCode($tournament, ['allowedSummonerIds' => $allowedSummonerIds]);
			$match->update(['code' => $code, 'status' => 'wait']);
		}
	}

	public function nextMatchCodeRTC($tournament, $numNext, $grid)
	{
		$gridNext = TGrids::select(['team1', 'team2', 'id'])
			->where('tournament_id', $tournament->id)
			->where('grid', $grid)
			->where('num', $numNext)->first();

		if ($gridNext->team1 && $gridNext->team2) {

			$current_timestamp = Carbon::now();
			$gridNext->update(['prepare_at' => $current_timestamp]);

			$allowedSummonerIds = config('games.lol.allowedSummonerIds');

			$players = TPlayers::select('profileId')
				->join('games_accounts', 'games_accounts.id', '=', 'tournaments_players.account_id')
				->where('tournament_id', $tournament->id)
				->whereIn('team', [$gridNext->team1, $gridNext->team2])
				->pluck('profileId')->toArray();

			$allowedSummonerIds = array_merge($allowedSummonerIds, $players);

			$code = $this->generateCode($tournament, ['allowedSummonerIds' => $allowedSummonerIds]);
			TMatches::where('grid_id', $gridNext->id)->first()->update(['code' => $code, 'status' => 'wait']);
			// TMatches::where('grid_id', $gridNext->id)->where('status', 'reserve')
			// 	->first()->update(['code' => $code, 'status' => 'wait']);
		}
	}
}
