<?php

namespace App\Http\Controllers\Api\v1\Tournaments;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use RiotAPI\LeagueAPI\LeagueAPI;

use App\Models\Tournament;
use App\Models\TStatistics;
use App\Models\TPlayers;
use App\Models\TMatches;
use App\Models\TGrids;
use App\Models\GameAccount;
use App\Models\GameProfile;
use GuzzleHttp\Client;

class CallbackController extends Controller
{
	protected $webhookUrl = 'https://discord.com/api/webhooks/891245160074203147/S1qEFBYAQgWu58wxU_SUSh5zfozaJPTrae2AMkxG_LnUfrLo9dljMlMyUcS9CDUxYDmD';

	public function switch(Request $request, $game)
	{

		$GameClass = $this->getGameClass($game);
		if (!$GameClass->callback($request))
			return response()->json(['success' => false, 'code' => 'callback.error_undefined']);
	}

	public function recalc()
	{
		\Config::set('app.debug', true);
		$game = 'lol';
		$type = 'rtc';

		// $date = '2021-07';
		$date = date('Y-m');

		// Очистка для полного пересчета
		// TStatistics::where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), $date)->delete();

		// TPlayers::where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), $date)
		//   ->where('role', 'success')->update(['role' => 'reserve']);

		// TMatches::where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), $date)
		// 	->where('status', 'success')->update(['status' => 'end']);
		// dd('Очистка');

		$tMatchs = TMatches::select([
			'id', 'grid_id', 'tournament_id',
			'status', 'code', 'match', 'match_id'
		])->with([
			'tournament' => function ($query) use ($game, $type) {
				$query->select(['id', 'game', 'type', 'round', 'start']);
				$query->where('game', $game);
				$query->where('type', $type);
			}
		])
			->where('created_at', 'LIKE', '%' . $date . '%')
			->whereIn('status', ['error', 'process', 'end'])
			->whereNotNull('match_id')->get();
		//   ->where('code', 'RU048fd-f6652bcc-dd8a-4b5a-9567-3e6194c4d3dc')->get();

		$tMatchs->each(function ($tMatch, $k) {
			echo $tMatch->id . '<br>';
			$dataMatch = $this->getMatchData($tMatch);

			if (!empty($dataMatch)) {
				$this->saveRTC($tMatch, $dataMatch);
			}
		});

		//За запас
		DB::transaction(function () use ($date) {

			$tPlayers = TPlayers::select(['id', 'user_id', 'created_at'])
				->where('role', 'reserve')->where('created_at', 'LIKE', '%' . $date . '%')->get();

			// $tStatsReserv = [];
			// $gameProfile = [];
			foreach ($tPlayers as $tPlayer) {

				// $tStatsReserv[] = [
				//   'user_id' => $tPlayer->user_id,
				//   'points'  => DB::raw('points + 5')
				// ];
				// $gameProfile[] = [
				//   'user_id'  => $tPlayer->user_id,
				//   'priority' => DB::raw('priority + 1')
				// ];


				$tPlayer->update(['role' => 'success']);

				TStatistics::where(
					DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'),
					Carbon::parse($tPlayer->created_at)->format('Y-m')
				)->updateOrCreate([
					'user_id' => $tPlayer->user_id,
					'game'    => 'lol',
					'type'    => 'rtc'
				], [
					'points'  => DB::raw('points + 5'),
					'lose'    => DB::raw('lose + 0'),
					'win'     => DB::raw('win  + 0'),
					'k'       => DB::raw('k + 0'),
					'd'       => DB::raw('d + 0'),
					'a'       => DB::raw('a + 0')
				]);

				// dd(Carbon::parse($tPlayer->created_at)->format('Y-m'), $tPlayer);

				// ->update(['points' => DB::raw('points + 5')]);

				// GameProfile::where('user_id', $tPlayer->user_id)->update(['priority'  => DB::raw('priority + 1')]);
				// GameProfile::whe($gameProfile, ['user_id'], ['priority']);
			}


			// GameProfile::upsert($gameProfile, ['user_id'], ['priority']);
			// dd($gameProfile);
			// TStatistics::upsert($tStatsReserv, ['user_id'], ['points'])->dd();
			// dd($tPlayers);
			// $tPlayers->update(['role' => 'success']);
			// dd($gameProfile);
		});
	}

	public function recalc2()
	{
		// \Config::set('app.debug', true);

		$tGrids = TGrids::select(['tournaments_grids.*'])
			->join('tournaments_matches', function ($join) {
				$join->on('tournaments_matches.grid_id', '=', 'tournaments_grids.id');
				$join->whereIn('tournaments_matches.status', ['process', 'end']);
			})
			->with([
				'tournament' => function ($query) {
					$query->select(['id', 'game', 'type', 'provider_id']);
				},
				'matches' => function ($query) {
					$query->select(['id', 'grid_id', 'status', 'win', 'code', 'match',  'match_id']);
				}
			])->get();

		$tGrids->each(function ($tGrid, $k) use (&$teams) {

			$tGrid->team1_score = 0;
			$tGrid->team2_score = 0;
			$tGrid->win 				= NULL;
			$matchSuccess 			= false;

			$tGrid->matches->each(function ($tMatch, $k) use ($tGrid, &$matchSuccess) {
				if (in_array($tMatch->status, ['end'])) {

					// TODO: Перенести в Game контроллер
					// $tGrid->tournament->game
					$dataMatch = $this->getMatchData($tMatch);
					// TODO: Перенести в Game контроллер

					if (!empty($dataMatch)) {

						$tMatch->win 		= $tGrid->{'team' . $dataMatch->win};
						$tMatch->status = 'success';
						$tMatch->match 	= json_encode($dataMatch->match);
						$tMatch->save();

						$matchSuccess 	= true;
					}
				}

				if ($tGrid->team1 == $tMatch->win)
					$tGrid->team1_score++;
				if ($tGrid->team2 == $tMatch->win)
					$tGrid->team2_score++;
			});

			if (ceil($tGrid->bo / 2) <= $tGrid->team1_score)
				$tGrid->win = $tGrid->team1;
			if (ceil($tGrid->bo / 2) <= $tGrid->team2_score)
				$tGrid->win = $tGrid->team2;

			$tGrid->save();

			if (!$tGrid->win && $tGrid->bo > 1 && $matchSuccess) {
				$match = $tGrid->matches->where('status', 'reserve')->first();
				if ($match) {
					$GameClass = $this->getGameClass($tGrid->tournament->game);
					$GameClass->matchCode($tGrid->tournament, $tGrid, $match);
				}
			}

			$TypeClass = $this->getTypeClass($tGrid->tournament->type);
			$TypeClass->matchCheck($tGrid);
		});

		return response()->json(['success' => true]);
	}


	// TODO: Перенести в Game контроллер
	public function getMatchData($tMatch, $gameId = null): object
	{
		if (!$tMatch->match) {



			try {

				// https://europe.api.riotgames.com/lol/match/v5/matches/RU_351438264
				// $riot   = new LeagueAPI(config('games.lol.api_config'));
				// $gameId = $gameId ? $gameId : $riot->getMatchIdsByTournamentCode($tMatch->code)[0];
				// $match  = $riot->getMatchByTournamentCode($gameId, $tMatch->code);

				$ch = curl_init();
				curl_setopt(
					$ch,
					CURLOPT_URL,
					'https://europe.api.riotgames.com/lol/match/v5/matches/' . $tMatch->match_id . '?api_key=' . config('games.lol.api_config.' .	LeagueAPI::SET_TOURNAMENT_KEY)
				);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$match  = curl_exec($ch);
				curl_close($ch);
			} catch (\Exception $e) {
				$tMatch->update(['status' => 'error']);
				$this->hookPost(['message' => $e->getMessage()]);
				return null;
			}
		} else $match = $tMatch->match;


		try {


			if (gettype($match) == 'string') $match = json_decode($match);

			// $players = [];
			// foreach ($match->info->participants as $v) {
			// 	$players[$v->summonerId] = $v;
			// 	$players[$v->summonerId]->summonerName = $v->summonerName;
			// }

			$teamWinKey   = $match->info->teams[0]->win ? 1 : 2;
		} catch (\Exception $e) {
			$tMatch->update(['status' => 'error']);
			$this->hookPost(['message' => $e->getMessage()]);
			return null;
		}

		return (object)['match' => $match, 'players' => $match->info->participants, 'win' => $teamWinKey];
	}

	public function saveRTC($tMatch, $data)
	{
		DB::transaction(function () use ($tMatch, $data) {

			$accounts = GameAccount::whereIn('profileId', array_keys($data->players))->get()->keyBy('profileId');
			if (!$accounts) return response()->json(['success' => false, 'code' => 'callback.mismatch_accounts']);

			$userIds  = $accounts->pluck('user_id');
			$tGrid    = TGrids::find($tMatch->grid_id);
			$profiles = GameProfile::select('mmr', 'id', 'user_id', 'game')
				->whereIn('user_id', $userIds)
				->where('game', $tMatch->tournament->game)
				->get()->keyBy('user_id');

			foreach ($data->players as $key => $part) {
				if (!isset($accounts[$key])) continue;
				$userId  = $accounts[$key]->user_id;
				$summonerName = $accounts[$key]->nickname;

				if ($summonerName != $part->summonerName) {
					$accountsValues[]  = ['profileId' => $key, 'nickname' => $part->summonerName];
					GameAccount::where('profileId', $key)->update(['nickname' => $part->summonerName]);
				}

				$points  = $part->win ? 150 : 50;
				$points += $part->kills + $part->assists - $part->deaths;
				$points *= 0.1;

				$mmr = $part->win ? 25 : -25;

				$profile = $profiles[$userId]->getRawOriginal();
				$profile['mmr']  += $mmr;
				$profile['mmr']   = $profile['mmr'] > config('games.lol.max_elo') ? config('games.lol.max_elo') : $profile['mmr'];
				$profile['mmr']   = $profile['mmr'] < config('games.lol.min_elo') ? config('games.lol.min_elo') : $profile['mmr'];
				$profileValues[]  = $profile;

				TStatistics::where(
					DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'),
					// '2021-04'
					Carbon::parse($tMatch->tournament->start)->format('Y-m')
				)->updateOrCreate([
					'user_id' => $userId,
					'game'    => $tMatch->tournament->game,
					'type'    => $tMatch->tournament->type
					// добавить дату с турнира
				], [
					// добавить дату с турнира
					'points'  => DB::raw('points + ' . $points),
					'lose'    => DB::raw('lose + '  . ($part->win ? 0 : 1)),
					'win'     => DB::raw('win  + '  . ($part->win ? 1 : 0)),
					'k'       => DB::raw('k + '     . $part->kills),
					'd'       => DB::raw('d + '     . $part->deaths),
					'a'       => DB::raw('a + '     . $part->assists)
				]);
			}

			// var_dump($accountsValues);
			// GameAccount::upsert($accountsValues, ['profileId'], ['nickname']);


			// dd($tMatch, $data);


			$teamWinId    = $tGrid->{'team' . $data->win};
			$tGrid->{'team' . $data->win . '_score'}++;
			// $tGrid->{'team' . $teamWinKey . '_score'} = 0;

			$boEnd      = $tGrid->bo / 2 < $tGrid->team1_score + $tGrid->team2_score;
			$boTeamId   = $tGrid->team1_score > $tGrid->team2_score ? $tGrid->team1 : $tGrid->team2;
			$boTeamWin  = $boEnd ? $boTeamId : null;

			GameProfile::upsert($profileValues, ['id'], ['mmr']);

			$tMatch->update([
				'match'   => json_encode($data->match),
				'status'  => 'success',
				'win'     => $teamWinId,
			]);

			$tGrid->update([
				'win' => $boTeamWin,
				'team1_score' => $tGrid->team1_score,
				'team2_score' => $tGrid->team2_score
			]);
		});
	}

	public function hookPost($data)
	{

		$client = new Client();
		// $color = $success ? 0x16b201 : 0xff0000;
		$color = 0xff0000;

		$hook_data = [
			'headers' => [
				'Content-Type' => 'application/json'
			],
			'json' => [
				'username' => 'Отчет по играм',
				// 'avatar_url' => '',
				'embeds' => [
					[
						// 'title' => "{$request->input('repository.name')}: {$request->input('push.changes.0.new.name')}",
						'title' => '<a:S3ChikaWink:583585238332997651> ',
						// "url" => $request->input('repository.links.html.href'),
						'color' => $color,
						'description' => $data['message'],
						// 'timestamp' => date()->now(),
						// 'author' => [
						// 'name' => "{$actor['display_name']} ({$actor['nickname']})",
						// 'icon_url' => $actor['links']['avatar']['href'],
						// 'url' =>  $request->input('push.changes.0.new.target.author.user.links.html.href')
						// ],
						// 'thumbnail' => [
						// 	'url' => 	$success ?
						// 		'http://bourkewaste.ie/wp-content/uploads/2014/06/tick-40143_1280.png' :
						// 		'https://cdn.discordapp.com/attachments/714998539867914271/787251934942986261/kisspng-computer-icons-clip-art-crossbones-cliparts-5b52921beb3629.8878202915321380119634.png'
						// ],
						// 'footer' => [
						// 	"text" => $request->input('repository.name'),
						// 	"icon_url" => $request->input('repository.links.avatar.href')
						// ],
						// 'fields' => [
						// [
						// 	'name' => 'Коментарий:',
						// 	'value' => $request->input('push.changes.0.new.target.message')
						// ],
						// [
						// 	'name' => 'Время:',
						// 	'value' => Carbon::now()->format('d.m.Y H:i:s'),
						// 	'inline' => true
						// ]
						// [
						// 	'name' => 'Коммитов:',
						// 	'value' => count($request->input('push.changes')),
						// 	'inline' => true
						// ],
						// [
						// 	'name' => 'Ветвь:',
						// 	'value' => "[{$request->input('push.changes.0.new.name')}](http://example.com)",
						// 	'inline' => true
						// ]
						// ]
					]
				]
			]
		];

		$client->post($this->webhookUrl, $hook_data);
	}
}
