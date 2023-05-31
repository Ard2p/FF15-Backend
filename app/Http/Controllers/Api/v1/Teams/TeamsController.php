<?php

namespace App\Http\Controllers\Api\v1\Teams;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\GameAccount;
use App\Models\GameProfile;
use App\Models\TPlayers;

class TeamsController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$game = 'lol';

		switch ($request->get('page')) {
			case 'avatars':
				$avatarsGroups = config('avatars_team');

				foreach ($avatarsGroups as $group => $avatarsGroup)
					if (\Perm::role($avatarsGroup['perm']))
						$data[$group] = $avatarsGroup['icons'];

				return response()->json($data);

				break;
			case 'invite':

				if (!\Perm::has('user@edit-self'))
					return response()->json(['success' => false], 403);

				if ($request->has('accept'))
					$accept = $request->boolean('accept');
				else $accept = 'empty';

				$code = $request->get('code');
				if (!$code)
					return response()->json(['success' => false, 'code' => 'team.empty_code']);

				$team = Team::where('code', $code)->first();

				if (!$team)
					return response()->json(['success' => false, 'code' => 'team.not_found']);

				if ($accept === 'empty') {

					$team = Team::where('id', $team->id)->with([
						'members' => function ($query) use ($game) {
							$query->select(['users.id', 'avatar', 'exp', 'role', 'nickname', 'team_id']);
							$query->join('users', function ($join) {
								$join->on('users.id', '=', 'teams_members.user_id');
							});
							$query->join('games_accounts', function ($join) use ($game) {
								$join->on('games_accounts.user_id', '=', 'teams_members.user_id');
								$join->where('games_accounts.active', true);
								$join->where('games_accounts.game', $game);
							});
						}
					])->first();

					return $team;
				}

				if ($accept === false)
					return response()->json(['success' => true, 'code' => 'team.not_accept']);

				if ($accept === true) {

					$user = \Auth::user();

					if (count($team->members) >= 6)
						return response()->json(['success' => false, 'code' => 'team.no_slot']);

					$isMember = TeamMember::where('user_id', $user->id)->first();

					if ($isMember)
						return response()->json(['success' => false, 'code' => 'team.already_member']);

					// $inActivTournament = TPlayers::select(['tournaments.status'])
					// 	->join('tournaments', 'tournaments.id', '=', 'tournaments_players.tournament_id')
					// 	->whereIn('tournaments.status', ['balance', 'process'])
					// 	->where('team_id', $isMember->team_id)->first();

					// if ($inActivTournament)
					// 	return response()->json(['success' => false, 'code' => 'team.in_activ_tournament'])->send();

					$accounts = GameAccount::where('game', $team->game)->where('user_id', $user->id)->get();
					if (!$accounts->count())
						return response()->json(['success' => false, 'code' => 'tournament.no_account', 'game' => $team->game])->send();

					$profile = GameProfile::where('game', $team->game)->where('user_id', $user->id)->first();
					if (!$profile)
						return response()->json([
							'success' => false, 'code' => 'tournament.no_profile',
							'game' => $team->game, 'league' => config('games.lol.leagues')
						])->send();

					$account = $accounts->where('active', true)->first();
					if (!$account)
						return response()->json(['success' => false, 'code' => 'tournament.no_account_active', 'game' => $team->game])->send();

					TeamMember::create([
						'team_id'	=> $team->id,
						'user_id' => $user->id,
						'status'	=> 'member'
					]);

					$team = Team::where('id', $team->id)->with([
						'members' => function ($query) use ($game) {
							$query->select(['users.id', 'avatar', 'exp', 'role', 'nickname', 'team_id']);
							$query->join('users', function ($join) {
								$join->on('users.id', '=', 'teams_members.user_id');
							});
							$query->join('games_accounts', function ($join) use ($game) {
								$join->on('games_accounts.user_id', '=', 'teams_members.user_id');
								$join->where('games_accounts.active', true);
								$join->where('games_accounts.game', $game);
							});
						}
					])->first();

					return $team;
				}

				return response()->json(['success' => false, 'code' => 'team.invite_switch_undefined']);

				break;
		}

		return response()->json(['success' => false, 'code' => 'team.page_undefined']);
	}

	public function switch(Request $request, $id)
	{
		switch ($request->get('event')) {
			case 'switch_owner':
				// return $this->swap($request, $id);
				break;
			case 'refresh_code':
				return $this->refresh_code($request, $id);
				break;
			case 'kick':
				// return $this->kick($request, $id);
				break;
			case 'leave':
				return $this->leave($request, $id);
				break;
		}
		return response()->json(['success' => false, 'code' => 'team.switch_undefined']);
	}

	public function refresh_code($request, $id)
	{
		$team = Team::where('id', $id)->with([
			'members' => function ($query) {
				$query->select('team_id', 'user_id', 'status');
			}
		])->first();

		if (!$team)
			return response()->json(['success' => false, 'code' => 'team.not_found']);

		$owner = $team->members->where('status', 'owner')->first();

		if (\Perm::denies('team@edit', $owner))
			return response()->json(['success' => false], 403);

		$team->update(['code' => \Str::uuid()]);

		return response()->json(['success' => true, 'data' => ['code' => $team->code]]);
	}

	public function leave($request, $id)
	{
		if (!\Perm::has('user@edit-self'))
			return response()->json(['success' => false], 403);

		$inActivTournament = TPlayers::select(['tournaments.status'])
			->join('tournaments', 'tournaments.id', '=', 'tournaments_players.tournament_id')
			->whereIn('tournaments.status', ['balance', 'process'])
			->where('team_id', $id)->first();

		if ($inActivTournament)
			return response()->json(['success' => false, 'code' => 'team.in_activ_tournament'])->send();

		$team = Team::where('id', $id)->with([
			'members' => function ($query) {
				$query->select('team_id', 'user_id', 'status');
			}
		])->first();

		if (!$team)
			return response()->json(['success' => false, 'code' => 'team.not_found']);

		$game = 'lol';
		$user = \Auth::user();

		$member = $team->members->where('user_id', $user->id)->first();
		if (!$member)
			return response()->json(['success' => false, 'code' => 'team.not_member']);

		if ($member->status === 'owner')
			return response()->json(['success' => false, 'code' => 'team.owner_not_leave']);

		TeamMember::where('team_id', $team->id)
			->where('user_id', $member->user_id)
			->delete();

		return response()->json(['success' => true]);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$game = 'lol';
		$user = \Auth::user();

		if (!\Perm::has('team@create'))
			return response()->json(['success' => false], 403);

		$accounts = GameAccount::where('game', $game)->where('user_id', $user->id)->get();
		if (!$accounts->count())
			return response()->json(['success' => false, 'code' => 'tournament.no_account', 'game' => $game])->send();

		$profile = GameProfile::where('game', $game)->where('user_id', $user->id)->first();
		if (!$profile)
			return response()->json([
				'success' => false, 'code' => 'tournament.no_profile',
				'game' => $game, 'league' => config('games.lol.leagues')
			])->send();

		$account = $accounts->where('active', true)->first();
		if (!$account)
			return response()->json(['success' => false, 'code' => 'tournament.no_account_active', 'game' => $game])->send();

		$messages = [
			'required' 	=> 'required',
			'unique' 		=> 'unique',
			'string' 		=> 'string',
			'integer' 	=> 'integer',
			'min' 			=> 'min::min',
			'max' 			=> 'max::max'
		];
		$validator = \Validator::make($request->all(), [
			'tag'				=> 'required|string|max:5|unique:teams,tag',
			'name'			=> 'required|string|max:30|unique:teams,name',
			'avatar'		=> 'required|integer',
			'category'	=> 'required|string'
		], $messages);

		if ($validator->fails())
			return response()->json(['success' => false, 'validator' => $validator->errors()->messages()]);



		// отправить в валидатор

		$category = config('avatars_team.' . $request->get('category') . '.icons');
		if (!$category)
			return response()->json(['success' => false, 'code' => 'avatar.category_mismatch']);

		if (!\Perm::role(config('avatars.' . $request->get('category') . '.perm')))
			return response()->json(['success' => false, 'code' => 'avatar.category_not_access']);

		$avatar = $category[$request->get('avatar')];
		if (!$avatar)
			return response()->json(['success' => false, 'code' => 'avatar.avatar_mismatch']);

		// отправить в валидатор


		$team = Team::create([
			'tag'			=> $request->get('tag'),
			'name' 		=> $request->get('name'),
			'avatar'	=> $avatar['path'],
			'mmr'			=> 1000,
			'status'	=> 'new',
			'game' 		=> $game,
			'code'		=> \Str::uuid()
		]);

		TeamMember::create([
			'team_id'	=> $team->id,
			'user_id' => \Auth::user()->id,
			'status'	=> 'owner'
		]);

		$team->members = [
			User::select(['users.id', 'avatar', 'exp', 'role', 'nickname'])
				->join('games_accounts', function ($join) use ($game) {
					$join->on('games_accounts.user_id', '=', 'users.id');
					$join->where('games_accounts.active', true);
					$join->where('games_accounts.game', $game);
				})->find(\Auth::user()->id)
		];

		return $team;
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		$game = 'lol';

		$inActivTournament = TPlayers::select(['tournaments.status'])
			->join('tournaments', 'tournaments.id', '=', 'tournaments_players.tournament_id')
			->whereIn('tournaments.status', ['balance', 'process'])
			->where('team_id', $id)->first();

		if ($inActivTournament)
			return response()->json(['success' => false, 'code' => 'team.in_activ_tournament']);

		$team = Team::where('id', $id)->with([
			'members' => function ($query) {
				$query->select('team_id', 'user_id', 'status');
			}
		])->first();

		if (!$team)
			return response()->json(['success' => false, 'code' => 'team.not_found']);

		$owner = $team->members->where('status', 'owner')->first();

		if (\Perm::denies('team@edit', $owner))
			return response()->json(['success' => false], 403);

		$messages = [
			'required' 	=> 'required',
			'unique' 		=> 'unique',
			'string' 		=> 'string',
			'integer' 	=> 'integer',
			'min' 			=> 'min::min',
			'max' 			=> 'max::max'
		];
		$validator = \Validator::make($request->all(), [
			'tag'				=> 'required|string|max:5|unique:teams,tag,' . $team->id,
			'name'			=> 'required|string|max:30|unique:teams,name,' . $team->id,
			'avatar'		=> 'integer',
			'category'	=> 'string'
		], $messages);

		if ($validator->fails())
			return response()->json(['success' => false, 'validator' => $validator->errors()->messages()]);

		$data = [
			'tag'			=> $request->get('tag'),
			'name' 		=> $request->get('name')
		];

		// отправить в валидатор

		if ($request->get('avatar') && $request->get('category')) {
			$category = config('avatars_team.' . $request->get('category') . '.icons');
			if (!$category)
				return response()->json(['success' => false, 'code' => 'avatar.category_mismatch']);

			if (!\Perm::role(config('avatars.' . $request->get('category') . '.perm')))
				return response()->json(['success' => false, 'code' => 'avatar.category_not_access']);

			$avatar = $category[$request->get('avatar')];
			if (!$avatar)
				return response()->json(['success' => false, 'code' => 'avatar.avatar_mismatch']);
			$data['avatar'] =  $avatar['path'];
		}
		// отправить в валидатор

		Team::where('id', $team->id)->update($data);
		return response()->json(['success' => true]);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Team $team)
	{
		$owner = $team->members->where('status', 'owner')->first();

		if (\Perm::denies('team@delete', $owner))
			return response()->json(['success' => false], 403);

		$inActivTournament = TPlayers::select(['tournaments.status'])
			->join('tournaments', 'tournaments.id', '=', 'tournaments_players.tournament_id')
			->whereIn('tournaments.status', ['balance', 'process'])
			->where('team_id', $team->id)->first();

		if ($inActivTournament)
			return response()->json(['success' => false, 'code' => 'team.in_activ_tournament'])->send();

		$team->delete();
		return response()->json(['success' => true]);
	}
}
