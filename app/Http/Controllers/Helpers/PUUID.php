<?php

namespace App\Http\Controllers\Helpers;

use App\Http\Controllers\Controller;

use RiotAPI\LeagueAPI\LeagueAPI;
use RiotAPI\LeagueAPI\Objects;

use App\Models\GameAccount;

class PUUID extends Controller
{
	public function __construct()
	{
	}

	public function index()
	{
		$accountsUpdate = [];

		$accounts = GameAccount::select(['accountId', 'nickname'])->whereNull('puuid')->limit(500)->get();

		$riot = new LeagueAPI(config('games.lol.api_config'));

		$accounts->each(function ($account, $k) use ($riot) {
			$summoner = $riot->getSummonerByAccountId($account->accountId);

			// $accountsUpdate[] = [
			// 	'accountId' => $account->accountId,
			// 	'puuid' 		=> $summoner->puuid,
			// 	'nickname'	=> $account->nickname != $summoner->name ? $summoner->name : $account->nickname,
			// ];

			// $account->puuid = $summoner->puuid;
			// $account->nickname = $account->nickname != $summoner->name ? $summoner->name : $account->nickname;
			// $account->save();

			GameAccount::where('accountId', $account->accountId)->update([
				'puuid' 		=> $summoner->puuid,
				'nickname'	=> $account->nickname != $summoner->name ? $summoner->name : $account->nickname,
			]);
		});

		// GameAccount::upsert($accountsUpdate, ['accountId'], ['puuid', 'nickname']);

		return response()->json('ok');
	}
}
