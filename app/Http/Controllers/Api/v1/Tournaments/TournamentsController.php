<?php

namespace App\Http\Controllers\Api\v1\Tournaments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Tournament;
use App\Models\TPlayers;
use App\Models\TGrids;
use App\Models\Team;

class TournamentsController extends Controller
{

	public function __construct()
	{
		$this->middleware('auth:api', ['except' => ['index', 'show', 'serviceIndex']]);
	}

	protected $select = [
		'id', 'user_id', 'name', 'img', 'desc', 'prize', 'round',
		'twitch', 'discord', 'game', 'type', 'start', 'status',
		'lvl', 'max_players', 'leave_disable', 'grid_disable'
	];

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$tournaments = Tournament::select($this->select);


		// try {
		// 	$start = new Carbon($request->get('start'));
		// 	$tournaments->where('start', '>=', $start->format('Y-m-d 00:00:00'));
		// } catch (\Exception $e) {}

		// try {
		// 	$start = new Carbon($request->get('end'));
		// 	$tournaments->where('start', '<=', $start->format('Y-m-d 23:59:59'));
		// } catch (\Exception $e) {}

		// ['create', 'pending', 'open', 'balance', 'process', 'end', 'arhive']
		$status = $request->get('status') !== 'end' ?
			['pending', 'open', 'balance', 'process'] : ['end'];
		$tournaments->whereIn('status', $status);
		$tournaments->orderBy('start', $request->get('status') !== 'end' ? 'asc' : 'desc');

		// ['rtc']
		// $tournaments->whereIn('type', (array)$request->get('type'));
		if ($request->get('type'))
			switch ($request->get('type')) {
				case 'team_all':
					$tournaments->whereIn('type', ['team', 'team_se', 'team_de']);
					break;
				case 'rtc_all':
					$tournaments->whereIn('type', ['rtc_se', 'rtc_de']);
					break;
				default:
					$tournaments->where('type', $request->get('type'));
					break;
			}


		// $tournaments->withCount('players');
		$tournaments->withCount(['players' => function ($query) {
			$query->whereRaw('tournaments_players.round = tournaments.round');
		}]);

		return $tournaments->paginate(10);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		if (\Perm::denies('tournament@create'))
			return response()->json(['success' => false], 403);

		$validator = \Validator::make($request->all(), [
			'name'        	=> 'required|max:191',
			// 'img'       => 'required|mimes:jpeg,bmp,png,gif|dimensions:ratio=16/9|max:2048',    // webm
			'desc'        	=> 'nullable|string',
			'prize'       	=> 'nullable|string',

			'twitch'      	=> 'nullable|string|max:100',
			'discord'     	=> 'nullable|string|max:100',                                            // url

			'game'        	=> 'required|string',
			'type'        	=> 'required|string',

			'lvl'         	=> 'nullable|integer',
			'max_players' 	=> 'nullable|integer',

			'leave_disable'	=> 'nullable|boolean',
			'grid_disable'	=> 'nullable|boolean',

			'start'       	=> 'required|after:tomorrow',
			'status'      	=> 'required|in:create,pending,open'
		]);
		// $validator['img'] = \Storage::putFile('tournaments/preview', $request->file('img'));

		if ($validator->fails())
			return response()->json($validator->errors());

		// $tournament = new Tournament($request->only([
		// 	'name', 'desc', 'prize', 'twitch', 'discord', 'game', 'type', 'lvl',
		// 	'max_players', 'start', 'status', 'leave_disable', 'grid_disable'
		// ]));

		$request->merge(['user_id' => \Auth::user()->id]);
		$tournament = Tournament::create($request->all());
		return $tournament;
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show(Request $request, $id)
	{
		$user = \Auth::user();

		// $tournament = \Cache::remember('Tournament@show_' . $id, 5, function () use ($id) {
		// 	return Tournament::select($this->select)->find($id);
		// });
		$tournament = Tournament::select($this->select)->find($id);

		$TypeClass = $this->getTypeClass($tournament->type);
		$TypeClass->show($tournament, $request);
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
		$tournament = Tournament::find($id);

		if (\Perm::denies('tournament@edit', $tournament))
			return response()->json(['success' => false], 403);

		if ($request->has('grid_disable'))
			if (\Perm::allows('tournament@toggle-grid')) {
				$tournament->update($request->only(['grid_disable']));
				return response()->json(['success' => true]);
			} else return response()->json(['success' => false], 403);

		if ($request->has('leave_disable'))
			if (\Perm::allows('tournament@toggle-leave')) {
				$tournament->update($request->only(['leave_disable']));
				return response()->json(['success' => true]);
			} else return response()->json(['success' => false], 403);

		$validator = \Validator::make($request->all(), [
			'name'        	=> 'required|max:255',
			// 'img'       => 'required|mimes:jpeg,bmp,png,gif|dimensions:ratio=16/9|max:2048',    // webm
			'desc'        	=> 'nullable|string',
			'prize'       	=> 'nullable|string',

			'twitch'      	=> 'nullable|string|max:100',
			'discord'     	=> 'nullable|string|max:100',

			'game'        	=> 'required|string',
			'type'        	=> 'required|string',

			'lvl'         	=> 'nullable|integer',
			'max_players' 	=> 'nullable|integer',

			'leave_disable'	=> 'nullable|boolean',
			'grid_disable'	=> 'nullable|boolean',

			'start'       	=> 'required|after:tomorrow',
			'status'      	=> 'required|in:create,pending,open'
		]);
		// $validator['img'] = \Storage::putFile('tournaments/preview', $request->file('img'));

		if ($validator->fails())
			return response()->json($validator->errors());

		$update = $tournament->update($request->only([
			'name', 'desc', 'prize', 'twitch', 'discord', 'game', 'type', 'lvl',
			'max_players', 'start', 'status', 'leave_disable', 'grid_disable'
		]));

		if (!$update)
			return response()->json(['success' => false, 'code' => 'tournament.error_update']);

		return $tournament;
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		$tournament = Tournament::find($id);

		if (\Perm::denies('tournaments@delete', $tournament))
			return response()->json(['success' => false], 403);

		if (!$tournament->delete())
			return response()->json([
				'success' => false,
				'code' => 'tournament.error_delete'
			]);

		return response()->json(['success' => true]);
	}

	public function serviceIndex(Request $request)
	{
		\Config::set('app.debug', true);

		$select = [
			'id', 'user_id', 'name', 'img', 'desc', 'prize', 'round',
			'twitch', 'discord', 'game', 'type', 'start', 'status',
			'lvl', 'max_players', 'leave_disable', 'grid_disable'
		];

		$tournaments = Tournament::select($select)
			->whereIn('status', ['open', 'balance', 'process'])
			->with([
				'grids' => function ($query) {

					$query->select([
						'id', 'tournament_id', 'round', 'grid', 'bo', 'win',
						'team1', 'team2', 'team1_score', 'team2_score'
					]);

					$query->with(['matches' => function ($query) {
						$query->select(['id', 'grid_id', 'tournament_id', 'status', 'win', 'code']);
					}]);
					// $query->makeVisible(['code']);
				},

				'players' => function ($query) {
					$query->select([
						'tournaments_players.user_id', 'tournaments_players.role', 'tournaments_players.status', 'grid_id',
						'tournament_id', 'round', 'team', 'nickname', 'avatar', 'exp', 'users.role AS site_role', 'note'
					]);

					$query->join('users',						'users.id',						'=', 'tournaments_players.user_id');
					$query->join('games_accounts',	'games_accounts.id',	'=', 'tournaments_players.account_id');

					$query->with([
						'profile' => function ($query) {
							$query->select(['user_id', 'game', 'mmr', 'priority', 'roles', 'mmr as elo', 'champions']);
							$query->where('game', 'lol');
						},
						'statistics' => function ($query) {
							$query->select('user_id', 'win', 'lose');
							$query->where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), date('Y-m'));
							$query->where('game', 'lol');
							$query->where('type',	'rtc');
						}
					]);
				},
			])->get();

		return $tournaments;

		// 	if (\Perm::allows('tournament@edit', $tournament)) {
		// 		$grids->each(function ($grid, $k) {
		// 			$grid->matches->makeVisible(['code']);
		// 		});
		// 	} else {
		// 		$player = $players->where('user_id', $user->id)
		// 			->where('round', $tournament->round)->first();
		// 		if ($player && $player->grid_id) {
		// 			$grid = $grids->where('id', $player->grid_id)->first();
		// 			if ($grid) $grid->matches->makeVisible(['code']);
		// 		}
		// 	}
		// }

		// return $tournament;
	}
}
