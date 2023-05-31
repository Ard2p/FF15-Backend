<?php

namespace App\Http\Controllers\Api\v1\Tournaments;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Tournament;

class AdminController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		if (\Perm::denies('tournament@edit'))
			return response()->json(['success' => false], 403);

		$game = 'lol';
		$tournaments = Tournament::where('game', $game);
		$tournaments->orderBy('start', 'desc');
		return $tournaments->paginate(20);
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
			'img'       		=> 'required|string',
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

			'start'       	=> 'required', //|after:tomorrow
			'status'      	=> 'required|in:create,pending,open'
		]);

		if ($validator->fails())
			return response()->json($validator->errors());

		// $validator['img'] = \Storage::putFile('tournaments/preview', $request->file('img'));
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
	public function show(Tournament $tournament)
	{
		if (\Perm::denies('tournament@edit'))
			return response()->json(['success' => false], 403);

		return $tournament;
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Tournament $tournament)
	{
		if (\Perm::denies('tournament@edit'))
			return response()->json(['success' => false], 403);

		$validator = \Validator::make($request->all(), [
			'name'        	=> 'required|max:191',
			'img'       		=> 'required|string',
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

			'start'       	=> 'required', // |after:tomorrow
			'status'      	=> 'required|in:create,pending,open'
		]);

		if ($validator->fails())
			return response()->json($validator->errors());

		if (!$tournament->update($request->all()))
			return response()->json(['success' => false, 'code' => 'tournaments.error_update']);

		return $tournament;
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Tournament $tournament)
	{
		if (\Perm::denies('tournament@delete'))
			return response()->json(['success' => false], 403);

		$tournament->delete();
		return response()->json(['success' => true]);
	}
}
