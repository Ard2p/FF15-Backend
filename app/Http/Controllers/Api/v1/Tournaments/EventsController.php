<?php

namespace App\Http\Controllers\Api\v1\Tournaments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use RiotAPI\LeagueAPI\LeagueAPI;
use RiotAPI\LeagueAPI\Objects;

use App\Models\Tournament;
use App\Models\TMatches;
use App\Models\TPlayers;
use App\Models\TGrids;
use App\Models\UPenalty;
use App\Models\GameAccount;
use App\Models\GameProfile;

use App\Http\Controllers\Api\v1\Tournaments\Types;

use App\Jobs\TournamentEvents;

class EventsController extends Controller
{

	public function switch(Request $request, $id)
	{
		// TournamentEvents::dispatch($request->get('event'))->onQueue('events');
		switch ($request->get('event')) {
			case 'enter':
				return $this->enter($request, $id);
				break;
			case 'leave':
				return $this->leave($request, $id);
				break;
			case 'swap':
				return $this->swap($request, $id);
				break;
			case 'status':
				return $this->status($request, $id);
				break;
			case 'kick':
				return $this->kick($request, $id);
				break;
			case 'delete_team':
				return $this->deleteTeam($request, $id);
				break;
			case 'regen':
				return $this->regen($request, $id);
				break;
			case 'match_status':
				return $this->matchStatus($request, $id);
				break;
			case 'new_round':
				return $this->newRound($request, $id);
				break;
			case 'delete_round':
				return $this->deleteRound($request, $id);
				break;
		}
		return response()->json(['success' => false, 'code' => 'tournament.error_undefined']);
	}

	public function enter($request, $id)
	{
		$user = \Auth::user();
		$allow_status = ['open', 'balance'];

		$tournament = Tournament::find($id);
		if (!$tournament)
			return response()->json(['success' => false, 'code' => 'tournament.not_found']);

		$playersCount = TPlayers::where('tournament_id', $tournament->id)->count();
		if ($playersCount >= $tournament->max_players)
			return response()->json(['success' => false, 'code' => 'tournament.max_players']);

		if (!in_array($tournament->status, $allow_status))
			return response()->json(['success' => false, 'code' => 'tournament.wrong_enter_status']);

		$penalty = UPenalty::where('user_id', $user->id)
			->where('type', 'ban')
			->where('end', '>', Carbon::now())->first();
		if ($penalty)
			return response()->json(['success' => false, 'code' => 'user.banned', 'penalty' => $penalty], 403);

		// Все заявки на турниры
		$select = ['tournaments.id AS tournament_id', 'name', 'tournaments.type', 'tournaments.status', 'start', 'game'];
		$tournaments_request = TPlayers::select($select)
			->join('tournaments', function ($join) {
				$join->on('tournaments.id', 		'=', 'tournaments_players.tournament_id');
				$join->on('tournaments.round', 	'=', 'tournaments_players.round');
			})
			->where('tournaments_players.user_id', $user->id)
			->whereIn('tournaments.status', ['open', 'balance', 'process'])
			->get();

		$TypeClass = $this->getTypeClass($tournament->type);
		if (!$TypeClass->enter($tournament, $tournaments_request))
			return response()->json(['success' => false, 'code' => 'tournament.error_enter']);

		$tournaments_request->push([
			'tournament_id' => $tournament->id,
			'name'          => $tournament->name,
			'type'          => $tournament->type,
			'status'        => $tournament->status,
			'start'         => $tournament->start,
			'game'          => $tournament->game,
			'round'         => $tournament->round
		]);
		return response()->json(['success' => true, 'tournaments_request' => $tournaments_request]);
	}

	public function leave($request, $id)
	{
		$allow_status = ['open', 'balance'];

		$tournament = Tournament::find($id);
		if (!$tournament)
			return response()->json(['success' => false, 'code' => 'tournament.not_found']);

		if (!in_array($tournament->status, $allow_status))
			return response()->json(['success' => false, 'code' => 'tournament.wrong_leave_status']);

		if ($tournament->leave_disable)
			return response()->json(['success' => false, 'code' => 'tournament.leave_disable']);

		$TypeClass = $this->getTypeClass($tournament->type);
		$TypeClass->leave($tournament);
	}

	public function swap($request, $id)
	{
		$allow_status = ['balance', 'process'];

		$tournament = Tournament::find($id);
		if (!$tournament)
			return response()->json(['success' => false, 'code' => 'tournament.not_found']);

		if (!in_array($tournament->type, ['rtc', 'rtc_se', 'rtc_de']))
			return response()->json(['success' => false, 'code' => 'tournament.not_type_swap']);

		if (\Perm::denies('tournament@edit', $tournament))
			return response()->json(['success' => false], 403);

		if (!in_array($tournament->status, $allow_status))
			return response()->json(['success' => false, 'code' => 'tournament.wrong_swap_status']);

		$userId = $request->get('user_id');
		$team		= $request->get('team');
		$role		= $request->get('role');
		$round	= $request->get('round');

		if (!$userId)
			return response()->json(['success' => false, 'code' => 'tournament.no_player_swap']);

		if (!$team || !$role) {
			TPlayers::where('user_id', $userId)
				->where('tournament_id', $tournament->id)
				->where('round', $round)
				->update(['team' => null, 'role' => null, 'grid_id' => null]);
			return response()->json(['success' => true]);
		}

		$swap_players = TPlayers::select(['id', 'team', 'role', 'grid_id', 'status'])
			->where('tournament_id', $id)
			->where('round', $round)
			->where(function ($query) use ($userId, $role, $team) {
				$query->where('user_id', $userId)
					->orWhere('team', $team)->where('role', $role);
			})->get();

		$grid_id = null;
		if ($swap_players->count() == 1) {

			$grid_id = TGrids::select('id')
				->where('tournament_id', $tournament->id)
				->where('round', $round)
				->where(function ($query) use ($team) {
					$query->where('team1', $team);
					$query->orWhere('team2', $team);
				})->pluck('id')[0];

			TPlayers::where('user_id', $userId)
				->where('tournament_id', $tournament->id)
				->where('round', $round)
				->update(['team' => $team, 'role' => $role, 'grid_id' => $grid_id]);
		}

		if ($swap_players->count() == 2) {
			$tmp = $swap_players[0]['id'];
			$swap_players[0]['id'] = $swap_players[1]['id'];
			$swap_players[1]['id'] = $tmp;
			$swap_players = $swap_players->toArray();

			$grid_id = $swap_players[0]['grid_id'] != null ? $swap_players[0]['grid_id'] : $swap_players[1]['grid_id'];

			TPlayers::find($swap_players[0]['id'])->update($swap_players[0]);
			TPlayers::find($swap_players[1]['id'])->update($swap_players[1]);
		}


		// Только для RTC
		if ($tournament->provider_id != null) {

			$allowedSummonerIds = config('games.lol.allowedSummonerIds');

			$players = TPlayers::select('profileId')
				->join('games_accounts', 'games_accounts.id', '=', 'tournaments_players.account_id')
				->where('grid_id', $grid_id)
				->pluck('profileId')->toArray();

			$allowedSummonerIds = array_merge($allowedSummonerIds, $players);

			$code = TMatches::select('code')
				->where('grid_id', $grid_id)
				->pluck('code')->first();

			// $riot = new LeagueAPI(config('games.lol.api_config'));
			$tournamentParams = new Objects\TournamentCodeUpdateParameters([
				'mapType'       		 => 'SUMMONERS_RIFT',
				'pickType'      		 => 'TOURNAMENT_DRAFT',
				'spectatorType' 		 => 'ALL',
				'allowedSummonerIds' => $allowedSummonerIds
			]);
			// $ff = $riot->editTournamentCode($code, $tournamentParams);

			$data = json_encode($tournamentParams);

			$ch = curl_init();
			curl_setopt(
				$ch,
				CURLOPT_URL,
				'https://americas.api.riotgames.com/lol/tournament/v4/codes/' . $code . '?api_key=' . config('games.lol.api_config.' .	LeagueAPI::SET_TOURNAMENT_KEY)
			);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response  = curl_exec($ch);
			curl_close($ch);
		}

		// // // $fffff = null;
		// // // $promise = $resultPromise->then(function ($result) use (&$fffff) {
		// // // 	$fffff = $result;
		// // // });

		// // // $promise->wait();
		// // dd($response);

		// return $this->resolveOrEnqueuePromise($resultPromise);
		// // return $this->resolveOrEnqueuePromise($resultPromise, function(array $result) {
		// // 	return $result;
		// // });

		return response()->json(['success' => true]);
	}

	public function status($request, $id)
	{
		\Config::set('app.debug', true);

		$tournament = Tournament::find($id);

		if (!$tournament)
			return response()->json(['success' => false, 'code' => 'tournament.not_found']);

		if (\Perm::denies('tournament@edit', $tournament))
			return response()->json(['success' => false], 403);

		$user = \Auth::user();

		$validator = \Validator::make($request->only('status'), [
			'status' => 'required|in:create,pending,open,balance,process,end,arhive'
		]);

		if ($validator->fails())
			return response()->json($validator->errors());

		$status =	$request->get('status');


		// Проверка на тип статуса
		if ($status == 'balance') {
			$TypeClass = $this->getTypeClass($tournament->type);

			if (!$TypeClass->balance($tournament))
				return response()->json(['success' => false, 'code' => 'tournament.error_balance']);
		}


		$tournament->update(['status' => $status]);
		return response()->json(['success' => true]);
	}

	public function kick($request, $id)
	{
		$tournament = Tournament::find($id);

		if (!$tournament)
			return response()->json(['success' => false, 'code' => 'tournament.not_found']);

		if (\Perm::denies('tournament@kick', $tournament))
			return response()->json(['success' => false], 403);

		$userId =	$request->get('user_id');

		TPlayers::where('user_id', $userId)
			->where('tournament_id', $tournament->id)
			->where('round', $tournament->round)
			->delete();

		return response()->json(['success' => true]);
	}

	public function regen($request, $id)
	{
		$tournament = Tournament::find($id);

		if (!$tournament)
			return response()->json(['success' => false, 'code' => 'tournament.not_found']);

		if (\Perm::denies('tournament@edit', $tournament))
			return response()->json(['success' => false], 403);

		$match = TMatches::where('id', $request->get('match'))
			->where('tournament_id', $tournament->id)->first();
		if (!$match)
			return response()->json(['success' => false, 'code' => 'tournament.match_not_found']);

		$code = null;
		if ($tournament->provider_id != null) {
			$riot = new LeagueAPI(config('games.lol.api_config'));
			$tournamentParams = new Objects\TournamentCodeParameters([
				'mapType'       	=> 'SUMMONERS_RIFT',
				'pickType'      	=> 'TOURNAMENT_DRAFT',
				'spectatorType' 	=> 'ALL',
				'teamSize'      	=> 5,
				'metadata'      	=> json_encode([
					'yek2'          => config('games.lol.token'),
					'tournament_id' => $tournament->id
				]),
			]);



			//TODO: зарефакторить
			if ($tournament->type == 'team_de') {

				$GameClass = $this->getGameClass($tournament->game);

				$match 	 = TMatches::with('grid')->find($request->get('match'));

				$nextNum = $match->grid->first()->num;
				$grid 	 = $match->grid->first()->grid;

				$GameClass->nextMatchCode($tournament, $nextNum, $grid);
				return response()->json(['success' => true]);
			}
			//TODO: зарефакторить



			$code = $riot->createTournamentCodes($tournament->provider_id, 1, $tournamentParams)[0];
		}

		if (!$code)
			return response()->json(['success' => false, 'code' => 'tournament.code_not_regen']);

		$match->update(['code' => $code, 'status' => 'wait']);

		return response()->json(['success' => true]);
	}

	public function matchStatus($request, $id)
	{
		$tournament = Tournament::find($id);
		$match_id 	= $request->get('id');
		$status 		= $request->get('status');
		$win 				= $request->get('win');

		if (!$tournament)
			return response()->json(['success' => false, 'code' => 'tournament.not_found']);

		if (\Perm::denies('tournament@edit', $tournament))
			return response()->json(['success' => false], 403);

		$match = TMatches::find($match_id);
		if (!$match)
			return response()->json(['success' => false, 'code' => 'tournament.match_not_found']);

		$grid = TGrids::with([
			'tournament' => function ($query) {
				$query->select(['id', 'game', 'type', 'provider_id']);
			}
		])->find($match->grid_id);
		if (!$grid)
			return response()->json(['success' => false, 'code' => 'tournament.grid_not_found']);

		$match->update([
			'status' => $status,
			'win' 	 => $win
		]);

		$grid->team1_score = 0;
		$grid->team2_score = 0;
		$grid->win 				 = NULL;

		$matchs = TMatches::where('grid_id', $grid->id)->get();
		$matchs->each(function ($match, $k) use ($grid) {
			if ($grid->team1 == $match->win)
				$grid->team1_score++;
			if ($grid->team2 == $match->win)
				$grid->team2_score++;
		});

		if (ceil($grid->bo / 2) <= $grid->team1_score)
			$grid->win = $grid->team1;
		if (ceil($grid->bo / 2) <= $grid->team2_score)
			$grid->win = $grid->team2;

		$grid->save();

		if (!$grid->win && $grid->bo > 1 && $status == 'success') {
			$match = $grid->matches->where('status', 'reserve')->first();
			if ($match) {
				$GameClass = $this->getGameClass($grid->tournament->game);
				$GameClass->matchCode($grid->tournament, $grid, $match);
			}
		}

		$TypeClass = $this->getTypeClass($grid->tournament->type);
		$TypeClass->matchCheck($grid);

		return response()->json(['success' => true]);
	}

	public function newRound($request, $id)
	{
		$tournament = Tournament::find($id);

		if (!$tournament)
			return response()->json(['success' => false, 'code' => 'tournament.not_found']);

		if ($tournament->type != 'rtc')
			return response()->json(['success' => false, 'code' => 'tournament.not_type_change_round']);

		if (\Perm::denies('tournament@edit', $tournament))
			return response()->json(['success' => false], 403);

		$tournament->round++;
		$tournament->status = 'open';
		$tournament->save();

		return response()->json(['success' => true]);
	}

	public function deleteRound($request, $id)
	{
		$tournament = Tournament::find($id);

		if (!$tournament)
			return response()->json(['success' => false, 'code' => 'tournament.not_found']);

		if ($tournament->type != 'rtc')
			return response()->json(['success' => false, 'code' => 'tournament.not_type_change_round']);

		if (\Perm::denies('tournament@edit', $tournament))
			return response()->json(['success' => false], 403);

		// TPlayers::where('tournament_id', $tournament->id)
		// 	->where('round', $tournament->round)
		// 	->delete();

		$tournament->round--;
		$tournament->status = 'end';
		$tournament->save();

		return response()->json(['success' => true]);
	}
}
