<?php

namespace App\Http\Controllers\Api\v1\Profiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use App\Models\Tournament;
use App\Models\TStatistics;
use App\Models\GameAccount;
use App\Models\GameProfile;

class AccountsController extends Controller
{
	public function __construct()
	{
		$this->middleware('sessions');
	}

	public function index(Request $request)
	{
		$game = 'lol';
		$user = \Auth::user();

		$select = ['id', 'user_id', 'mmr', 'roles'];

		$profile = GameProfile::select($select)->with([
			// 'accounts' => function ($query) use ($game) {
			// 	$query->select('id', 'user_id', 'game', 'nickname', 'active');
			// 	$query->where('game', $game);
			// },
			'statistics' => function ($query) use ($game) {
				$query->select('user_id', 'win', 'lose', 'points');
				$query->where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), date('Y-m'));
				$query->where('game', $game);
				$query->where('type',	'rtc');
			}
		])->where('game', $game)->where('user_id', $user->id)->first();

		if ($profile) $profile->rating = isset($profile->statistics) ?
			TStatistics::where('points', '>', $profile->statistics->points)->count() + 1 : null;

		$profile['accounts'] = GameAccount::select(['id', 'user_id', 'game', 'nickname', 'active'])
			->where('user_id', 	$user->id)->where('game', $game)->get();

		return response()->json($profile);
	}

	public function update(Request $request, $account_id)
	{
		if (!\Perm::has('account@update-self'))
			return response()->json(['success' => false], 403);

		$game = 'lol';
		$user = \Auth::user();

		DB::beginTransaction();

		$old_account = GameAccount::where('user_id', $user->id)
			->where('game', $game)->where('active', true)
			->update(['active' => false]);

		$new_account = GameAccount::where('user_id', $user->id)
			->where('game', $game)->where('id', $account_id)
			->update(['active' => true]);

		if (!$old_account || !$new_account) {
			DB::rollback();
			return response()->json(['success' => false, 'code' => 'account.error_undefined']);
		}

		DB::commit();
		return response()->json(['success' => true, 'data' => $user]);
	}

	public function destroy(Request $request, $account_id)
	{
		if (!\Perm::has('account@deactivate-self'))
			return response()->json(['success' => false], 403);

		return response()->json(['success' => false, 'code' => 'account.delete_off']);
		// $game = 'lol';
		// $user = \Auth::user();

		// return response()->json(['success' => true]);
	}

	public function store(Request $request)
	{
		if (!\Perm::has('account@update-self'))
			return response()->json(['success' => false], 403);

		switch ($request->get('event')) {
			case 'get_account':
				return $this->getAccount($request);
				break;
			case 'check_account':
				return $this->checkAccount($request);
				break;
			case 'set_data':
				return $this->setData($request);
				break;
		}

		return response()->json(['success' => false, 'code' => 'account.error_undefined']);
	}

	public function getAccount($request)
	{
		$riot = $this->riotAPI();
		$user = \Auth::user();
		$game = $request->get('game');

		if ($game == '')
			return response()->json(['success' => false, 'code' => 'account.empty_game']);

		if ($request->get('summonername') == '')
			return response()->json(['success' => false, 'code' => 'account.empty_name']);

		try {
			$summoner = $riot->getSummonerByName($request->input('summonername'));
		} catch (\Throwable $e) {
			return response()->json(['success' => false, 'code' => 'account.summoner_' . $e->getCode()]);
		}

		$account = GameAccount::where('game', $game)->where('profileId', $summoner->id)->first();
		if ($account)
			return response()->json(['success' => false, 'code' => 'account.already']);

		$iconId = random_int(0, 28);
		if ($summoner->profileIconId == $iconId)
			$iconId = random_int(0, 28);

		session([
			$game . '.profileIconId'	=> $iconId,
			$game . '.summonerName'		=> $summoner->name,
			$game . '.profileId'			=> $summoner->id
		]);

		return response()->json(['success' => true, 'step' => 'check_account', 'set_icon' => $iconId, 'summoner_name' => $summoner->name]);
	}

	public function checkAccount($request)
	{
		$riot = $this->riotAPI();
		$user = \Auth::user();
		$game = $request->get('game');

		if ($game == '')
			return response()->json(['success' => false, 'code' => 'account.empty_game']);

		if (\App::environment('dev')) {

			$account = $user->accounts()->where('game', $game)->first();
			$active = $account ? false : true;

			$user->accounts()->create([
				'game'      => $game,
				'nickname'  => 'Player_' . \Str::random(10),
				'profileId' => \Str::random(10),
				'accountId' => \Str::random(10),
				'active'    => $active,
			]);
		} else {

			if (!session($game . '.profileIconId') || !session($game . '.profileId'))
				return response()->json(['success' => false, 'code' => 'account.empty_session']);

			try {
				$summoner = $riot->getSummoner(session($game . '.profileId'));
			} catch (\Throwable $e) {
				return response()->json(['success' => false, 'code' => 'account.summoner_' . $e->getCode()]);
			}

			if ($summoner->profileIconId != session($game . '.profileIconId'))
				return response()->json(['success' => false, 'code' => 'account.icon_mismatch']);

			$account = $user->accounts()->where('game', $game)->first();
			$active = $account ? false : true;

			$user->accounts()->create([
				'game'      => $game,
				'nickname'  => $summoner->name,
				'profileId' => $summoner->id,
				'accountId' => $summoner->accountId,
				'active'    => $active,
			]);

			session()->forget([$game . '.profileIconId', $game . '.summonerName', $game . '.profileId']);
		}

		$profile = $user->profiles()->where('game', $game)->first();
		if ($profile)
			return response()->json(['success' => true, 'step' => 'success']);

		return response()->json(['success' => true, 'step' => 'set_data', 'league' => config('games.lol.leagues')]);
	}

	public function setData($request)
	{
		$user			= \Auth::user();
		$game			= $request->get('game');

		$roles 		= $request->get('roles');
		$league		= $request->get('league');
		$division = $request->get('division');

		$profile = $user->profiles()->where('game', $game)->first();
		if ($profile)
			return response()->json(['success' => false, 'code' => 'account.profile_already']);

		if (!$game || !$roles || !$league || !$division)
			return response()->json(['success' => false, 'code' => 'account.empty_data']);

		if (!count($roles) == 5 || count(array_diff(['top', 'jung', 'mid', 'adc', 'sup'], $roles)) != 0)
			return response()->json(['success' => false, 'code' => 'account.wrong_roles']);

		$config = config('games.lol.leagues');
		if (!array_key_exists($league, $config) || !array_key_exists($division, $config[$league]))
			return response()->json(['success' => false, 'code' => 'account.rang_mismatch']);

		$user->profiles()->create([
			'game'	=> $game,
			'mmr'		=> $config[$league][$division],
			'roles'	=> $roles
		]);

		return response()->json(['success' => true, 'step' => 'success']);
	}
}
