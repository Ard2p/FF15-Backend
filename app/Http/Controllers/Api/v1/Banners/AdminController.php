<?php

namespace App\Http\Controllers\Api\v1\Banners;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Banner;

class AdminController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		if (\Perm::denies('banner@edit'))
			return response()->json(['success' => false], 403);

		$game = 'lol';
		$banners = Banner::where('game', $game);
		return $banners->paginate(20);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		if (\Perm::denies('banner@create'))
			return response()->json(['success' => false], 403);

		$validator = \Validator::make($request->all(), [
			'title'        		=> 'required|max:191',
			'img'       			=> 'required|string',
			//'img'       => 'required|mimes:jpeg,bmp,png,gif|dimensions:ratio=16/9|max:2048',    // webm

			'btn_name'     		=> 'nullable|string',
			'btn_link'     		=> 'nullable|string',

			'game'         		=> 'required|string',
			'category'        => 'nullable|string',

			'status'          => 'required|in:publish,draft'
		]);
		// $validator['img'] = \Storage::putFile('tournaments/preview', $request->file('img'));

		if ($validator->fails())
			return response()->json($validator->errors());

		$banner = Banner::create($request->all());
		return $banner;
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show(Banner $banner)
	{
		if (\Perm::denies('banner@edit'))
			return response()->json(['success' => false], 403);

		return $banner;
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Banner $banner)
	{

		if (\Perm::denies('banner@edit'))
			return response()->json(['success' => false], 403);

		$validator = \Validator::make($request->all(), [
			'title'        		=> 'required|max:191',
			'img'       			=> 'required|string',
			// => 'required|mimes:jpeg,bmp,png,gif|dimensions:ratio=16/9|max:2048',    // webm

			'btn_name'     		=> 'nullable|string',
			'btn_link'     		=> 'nullable|string',

			'game'         		=> 'required|string',
			'category'        => 'nullable|string',

			'status'          => 'required|in:publish,draft'
		]);

		if ($validator->fails())
			return response()->json($validator->errors());

		if (!$banner->update($request->all()))
			return response()->json(['success' => false, 'code' => 'banners.error_update']);

		return $banner;
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Banner $banner)
	{
		if (\Perm::denies('banner@delete'))
			return response()->json(['success' => false], 403);

		$banner->delete();
		return response()->json(['success' => true]);
	}
}
