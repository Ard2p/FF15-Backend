<?php

namespace App\Http\Controllers\Api\v1\Ratings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

use App\Models\TPlayers;
use App\Models\TStatistics;

class RatingsController extends Controller
{

	protected $select = [
		'points', 'win', 'lose', 'k', 'd', 'a',
	];

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$game = 'lol';
		// $date = date('Y-m');
		$date = $request->get('date');

		$statistics = TStatistics::select([
			'user_id', 'points',
		'game', 'type',
		'win', 'lose',
		'k', 'd', 'a', 
			'users.avatar',
			'users.exp', 'users.role', 'users.status'
		])
			->where(DB::raw('DATE_FORMAT(tournaments_statistics.created_at , \'%Y-%m\')'), $date)
			->where('tournaments_statistics.game', $game)
			->join('users', 'users.id', '=', 'tournaments_statistics.user_id')
			->with([
				'account' => function ($query) use ($game) {
					$query->select('user_id', 'nickname');
					$query->where('game',		$game);
					$query->where('active',	true);
				}
			])->orderBy('points', 'desc')->get();

		return $statistics;
	}
}
