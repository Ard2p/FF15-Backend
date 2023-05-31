<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\USocials;
use App\Models\TPlayers;
use App\Models\GameAccount;
use App\Models\Team;

use App\Http\Controllers\Controller;
use App\Exceptions\EmailTakenException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Cookie;

class OAuthController extends Controller
{

	public function __construct()
	{
		$this->middleware('cookies');
	}

	/**
	 * Redirect the user to the provider authentication page.
	 *
	 * @param  string $provider
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function redirectToProvider($provider)
	{
		// return ['url' => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl()];
		return redirect(Socialite::driver($provider)->stateless()->redirect()->getTargetUrl());
	}

	/**
	 * Obtain the user information from the provider.
	 *
	 * @param  string $driver
	 * @return \Illuminate\Http\Response
	 */
	public function handleProviderCallback(Request $request, $provider)
	{
		$user = Socialite::driver($provider)->stateless()->user();
		$isAuth = \Auth::check();
		$user = $this->findOrCreateUser($provider, $user, $request);

		$token = auth()->login($user);
		$this->responseToken($token);

		return $isAuth ? redirect('/settings') : redirect('/');
	}

	/**
	 * @param  string $provider
	 * @param  \Laravel\Socialite\Contracts\User $sUser
	 * @return \App\User|false
	 */
	public function findOrCreateUser($provider, $socialAccount, $request)
	{
		$account = USocials::whereProvider($provider)
			->whereProviderUserId($socialAccount->getId())->first();

		if ($account) {
			return $account->user;
		} else {
			$account = new USocials([
				'provider_user_id' => $socialAccount->getId(),
				'provider' => $provider
			]);

			if (\Auth::check()) $user = \Auth::user();
			else {
				$user 		= $socialAccount->getEmail() ? User::whereEmail($socialAccount->getEmail())->first() : null;
				$ref 			= $request->get('ref');
				$referrer = $ref ? User::where('ref_code', $ref)->first() : null;
				$referrer = $referrer ? $referrer->id : null;
				if (!$user) {
					$user = User::create([
						'email' 		=> $socialAccount->getEmail(),
						'ref_code' 	=> \Str::uuid(),
						'referrer' 	=> $referrer,
						'avatar'		=> '0',
						'exp'				=> 1000
					]);
					$user->markEmailAsVerified();
				}
			}

			$account->user()->associate($user);
			$account->save();
			return $user;
		}
	}

	public function destroyProvider($provider)
	{
		$user = \Auth::user();
		$accounts = USocials::where('user_id', $user->id)->get();

		if (count($accounts) <= 1 && !$user->hasVerifiedEmail())
			return response()->json([
				'success' => false,
				'code'    => 'auth.social_is_last'
			]);

		$account = $accounts->where('provider', $provider)->first();
		if (!$account)
			return response()->json([
				'success' => false,
				'code'    => 'auth.no_match_social'
			]);

		$account->delete();
		return response()->json(['success' => true]);
	}


	public function profile(Request $request)
	{
		$game = 'lol';

		$select = [
			'id', 'avatar', 'exp',
			'role',	'users.status', 'email'
		];

		$user = User::select($select)->find(\Auth::user()->id);
		$user->getPermissions();

		$user->team = Team::select('team_id', 'name', 'tag', 'avatar', 'teams.status AS status', 'teams_members.status AS member')
			->join('teams_members', 'teams.id', '=', 'teams_members.team_id')
			->where('teams_members.user_id', $user->id)
			->where('game', $game)->first();

		$select = [
			'tournaments.id AS tournament_id', 'tournaments.round',
			'tournaments.type', 'name', 'tournaments.status', 'start', 'game',
			'tournaments_players.user_id', 'team_id'
		];

		$user->tournaments_request = TPlayers::select($select)
			->join('tournaments', function ($join) {
				$join->on('tournaments.id', 		'=', 'tournaments_players.tournament_id');
				$join->on('tournaments.round', 	'=', 'tournaments_players.round');
			})
			->where(function ($query) use ($user) {
				$query->where('tournaments_players.user_id', $user->id);
				if ($user->team)
					$query->orWhere('tournaments_players.team_id', $user->team->team_id);
			})->whereIn('tournaments.status', ['open', 'balance', 'process'])->get();

		return response()->json([
			'status' => 'success',
			'data' => $user,
		]);
	}
	// "select `tournaments`.`id` as `tournament_id`, `tournaments`.`round`, `tournaments`.`type`, `name`, `status`, `start`, `game` from `tournaments_players` inner join `tournaments` on `tournaments`.`id` = `tournaments_players`.`tournament_id` where `tournaments_players`.`round` = ? and `tournaments_players`.`user_id` = ? and `status` in (?, ?, ?)"

	public function logout()
	{
		auth()->logout();
		return response()->json(['status' => 'success'], 200);
	}

	// public function logout()
	// {
	// 	$this->guard()->logout();
	// 	return response()->json([
	// 		'status' => 'success',
	// 		'message' => 'Logged out Successfully.'
	// 	], 200);
	// }

	// private function guard()
	// {
	// 	return \Auth::guard();
	// }

	protected function responseToken($token)
	{
		Cookie::queue('token', $token, config('jwt.ttl'), '/', null, true, true, false, 'lax');
	}
}
