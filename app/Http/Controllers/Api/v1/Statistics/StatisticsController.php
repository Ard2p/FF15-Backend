<?php

namespace App\Http\Controllers\Api\v1\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use App\Models\User;
use App\Models\TMatches;
use App\Models\GameProfile;

class StatisticsController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		\Config::set('app.debug', true);

		$game = 'lol';
		$date = $request->get('date');

		$statistics = json_decode(Storage::get('statistics/lol_rtc_' . $date . '.json'), true);
		return $statistics;
	}

	public function recalc()
	{
		\Config::set('app.debug', true);

		// $date = '2021-04';
		$date = date('Y-m');

		$matches = TMatches::select('match')
			->join('tournaments', function ($join) use ($date) {
				$join->on('tournaments.id', 				'=', 'tournaments_matches.tournament_id');
				$join->where('tournaments.type', 		'=', 'rtc');
				// $join->where('tournaments.status',	'=', 'end');
				$join->where(DB::raw('DATE_FORMAT(tournaments.start, \'%Y-%m\')'), $date);
			})
			->where('match',	'<>', null)
			->pluck('match');

		$json_champions = file_get_contents('http://ddragon.leagueoflegends.com/cdn/11.17.1/data/ru_RU/champion.json');
		$list_champions = json_decode($json_champions)->data;

		$championsIds = [];
		foreach ($list_champions as $key => $champion) {
			$championsIds[$champion->key] = [
				'name' => $champion->name,
				'icon' => $champion->id
			];
		}

		$gamesCount = 0;
		$summoners = [];
		$champions = [];
		$players = [];
		foreach ($matches as $match) {
			$gamesCount++;

			if (gettype($match) == 'string') $match = json_decode($match);

			foreach ($match->participants as $v) {
				$player = $match->participantIdentities[array_search(
					$v->participantId,
					array_column($match->participantIdentities, 'participantId')
				)]->player;

				$championId		= $v->championId;
				$championName = $championsIds[$championId]['name'];
				$championIcon = $championsIds[$championId]['icon'];

				if (isset($summoners[$player->summonerId][$championId]))
					$summoners[$player->summonerId][$championId]++;
				else $summoners[$player->summonerId][$championId] = 1;

				if (!isset($champions[$championId]))
					$champions[$championId] = [
						'name' 		=> $championName,
						'icon' 		=> $championIcon,
						'ban' 		=> 0,
						'win' 		=> 0,
						'rang' 		=> '',
						'games' 	=> 0,
						'players' => [],
						// 'damageDealtToTurrets' => 0,
						// 'damageDealtToChampions' => 0
					];

				if ($v->stats->win) $champions[$championId]['win']++;

				$champions[$championId]['games']++;

				// $champions[$championId]['damageDealtToTurrets'] 	+= $v->stats->damageDealtToTurrets;
				// $champions[$championId]['damageDealtToChampions'] += $v->stats->totalDamageDealtToChampions;
			}

			foreach ($match->teams as $team) {
				foreach ($team->bans as $ban) {

					$championId		= $ban->championId;
					$championName = $championsIds[$championId]['name'];
					$championIcon = $championsIds[$championId]['icon'];

					if (!isset($champions[$ban->championId]))
						$champions[$ban->championId] = [
							'name' 		=> $championName,
							'icon' 		=> $championIcon,
							'ban' 		=> 0,
							'win' 		=> 0,
							'rang' 		=> '',
							'games' 	=> 0,
							'players' => [],
							// 'damageDealtToTurrets' => 0,
							// 'damageDealtToChampions' => 0
						];
					$champions[$ban->championId]['ban']++;
				}
			}
		}

		$users = User::select(['profileId', 'users.id', 'avatar', 'role', 'nickname'])
			->join('games_accounts', 'games_accounts.user_id', '=', 'users.id')
			->whereIn('profileId', array_keys($summoners))->get()->keyBy('profileId')->toArray();

		foreach ($users as $key => $user) {
			foreach ($summoners[$key] as $championId => $games) {
				if (!isset($champions[$championId]['players'][$user['id']])) {
					unset($user['profileId']);
					$champions[$championId]['players'][$user['id']] = $user;
					$champions[$championId]['players'][$user['id']]['games'] = 0;
				}
				$champions[$championId]['players'][$user['id']]['games'] += $games;

				if (!isset($players[$user['id']])) {
					$players[$user['id']] = [
						'user_id' => $user['id'],
						'game' => 'lol',
						'champions' => []
					];
				}
				if (!isset($players[$user['id']]['champions'][$championId])) {
					$players[$user['id']]['champions'][$championId] = [
						'id' 	 => $championId,
						'icon' => $champions[$championId]['icon'],
						'name' => $champions[$championId]['name'],
						'games' => 0
					];
				}
				$players[$user['id']]['champions'][$championId]['games'] += $games;
				// $champions[$championId]['players'][$user['id']] = array_values($champions[$championId]['players'][$user['id']]);
			}
		}

		foreach ($champions as $championId => $champion) {
			uasort($champions[$championId]['players'], 	function ($a, $b) {
				if ($a['games'] == $b['games']) return 0;
				return ($a['games'] > $b['games']) ? -1 : 1;
			});

			$champions[$championId]['players'] = array_slice($champions[$championId]['players'], 0, 5);
			// $champions[$championId] = array_values($champions[$championId]);
		}

		foreach ($players as $id => $player) {
			uasort($players[$id]['champions'], 	function ($a, $b) {
				if ($a['games'] == $b['games']) return 0;
				return ($a['games'] > $b['games']) ? -1 : 1;
			});

			$players[$id]['champions'] = array_slice($players[$id]['champions'], 0, 5);
			GameProfile::where('user_id', $id)->where('game', 'lol')->update(['champions' => $players[$id]['champions']]);
		}

		// dd(array_values($players));
		// GameProfile::upsert($players, ['user_id'], ['champions']);

		$statistics = json_encode([
			'date' 			=> date("Y-m-d H:i:s"),
			'games' 		=> $gamesCount,
			'champions'	=> array_values($champions)
		]);

		Storage::put('statistics/lol_rtc_' . $date . '.json', $statistics);
	}
}
