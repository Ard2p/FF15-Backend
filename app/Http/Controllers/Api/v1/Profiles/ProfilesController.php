<?php

namespace App\Http\Controllers\Api\v1\Profiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

use App\Models\User;
use App\Models\TPlayers;
use App\Models\UPenalty;
use App\Models\GameAccount;
use App\Models\TStatistics;

class ProfilesController extends Controller
{

	protected $select = [
		'users.id as user_id', 'users.role', 'users.status', 'users.exp', 'users.avatar'

	];
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		// $users = User::select()
		// 	->join('games_profiles', 'games_profiles.user_id', '=', 'users.id')
		// 	->join('games_accounts', 'games_accounts.user_id', '=', 'users.id');

		// $users = User::select($this->select)
		// 	->with('accounts')->with('profiles');
		// return $users->paginate(0);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$game = 'lol';
		$select = [
			'id', 'id as user_id', 'role', 'status', 'exp', 'avatar', 'referrer',
			'ref_code', 'email', 'email_verified_at', 'created_at'
		];
		$user = User::select($select)
			->where('id', $id)
			->with([
				'profile' => function ($query) use ($game) {
					$query->select('id', 'user_id', 'game', 'mmr', 'roles', 'champions');
					$query->where('game', $game);
				},
				'accounts' => function ($query) use ($game) {
					$query->select('id', 'user_id', 'game', 'nickname', 'active');
					$query->where('game', $game);
				},
				'statistics' => function ($query) use ($game) {
					$query->select('user_id', 'points', 'created_at', 'win', 'lose');
					$query->where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), date('Y-m'));
					$query->where('game', $game);
					$query->where('type', 'rtc');
				}
			])->first();

		if (!$user) return response()->json(['success' => false], 404);

		$user->rating = isset($user->statistics) ?
			TStatistics::where('points', '>', $user->statistics->points)
			->where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), date('Y-m'))
			->count() + 1 : null;

		return response()->json($user);
	}

	public function search(Request $request)
	{
		$game = 'lol';
		$nickname = $request->get('nickname');

		$select = [
			'games_accounts.user_id', 'game', 'nickname', 'active', 'role', 'exp', 'avatar'
		];

		$accounts = GameAccount::select($select)
			->join('users', 'users.id', 'games_accounts.user_id')
			->where('games_accounts.nickname', 'LIKE', '\\' . $nickname . '%')
			->where('games_accounts.game', $game)
			->limit(20);

		return response()->json($accounts->get());
	}

	public function update(Request $request, User $user)
	{
		switch ($request->get('update')) {
			case 'note':
				if (\Perm::denies('user@note'))
					return response()->json(['success' => false], 403);
				return $this->updateNote($request, $user);
				break;
			case 'ban':
				if (\Perm::denies('user@ban'))
					return response()->json(['success' => false], 403);
				return $this->updateBan($request, $user);
				break;
		}
	}

	public function updateNote(Request $request, $user)
	{
		$messages = ['required' => 'required', 'string' => 'string', 'email' => 'email', 'max' => 'max::max'];
		$validator = \Validator::make($request->all(), [
			'note' => 'required|string',
		], $messages);

		if ($validator->fails()) return response()->json([
			'success' => false,
			'errors' 	=> $validator->errors()->messages()
		], 200);

		$user->update(['note' => $request->get('note')]);
		return response()->json(['success' => true]);
	}

	public function updateBan(Request $request, $user)
	{
		$request->merge(['type' => 'ban']);
		$validator = \Validator::make($request->all(), [
			'type'		=> 'required|string',
			'reason'	=> 'nullable|string',
			'end'			=> 'required|after:tomorrow'
		]);

		if ($validator->fails())
			return response()->json($validator->errors());

		if (!$user)
			return response()->json(['success' => false, 'code' => 'user.not_found']);

		$penalty = UPenalty::where('user_id', $user->id)
			->where('type', 'ban')
			->where('end', '>', Carbon::now())->first();
		if ($penalty)
			return response()->json(['success' => false, 'code' => 'user.penalty_already']);

		$request->merge(['user_id' => $user->id]);
		UPenalty::create($request->all());

		$select = [
			'*'
			// 'tournaments.id AS tournament_id', 'tournaments.type',
			// 'tournaments.round', 'name', 'status', 'start', 'game'
		];

		TPlayers::select($select)
			->join('tournaments', function ($join) {
				$join->on('tournaments.id', 		'=', 'tournaments_players.tournament_id');
				$join->on('tournaments.round', 	'=', 'tournaments_players.round');
			})
			->where('tournaments_players.user_id', $user->id)
			->whereIn('tournaments.status', ['open'])
			->delete();

		return response()->json(['success' => true]);
	}
}
