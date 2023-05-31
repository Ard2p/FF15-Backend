<?php

namespace App\Http\Controllers\Api\v1\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\ShopCategory;

// 'shop@create',
// 'shop@edit',
// 'shop@delete',


class AdminCategoryController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$categories = ShopCategory::all();
		return response()->json($categories);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		// $user = \Auth::user();

		if (!\Perm::has('shop@create'))
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
			'name'			=> 'required|string',
			'slug'			=> 'required|string|unique:shop_categories,slug'
		], $messages);

		if ($validator->fails())
			return response()->json(['success' => false, 'validator' => $validator->errors()->messages()]);

		$category = ShopCategory::create($request->only(['name', 'slug']));
		return response()->json($category);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show(ShopCategory $category)
	{
		return response()->json($category);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, ShopCategory $category)
	{
		if (!\Perm::has('shop@edit'))
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
			'name'			=> 'required|string',
			'slug'			=> 'required|string|unique:shop_categories,slug,' . $category->slug
		], $messages);

		if ($validator->fails())
			return response()->json(['success' => false, 'validator' => $validator->errors()->messages()]);

		$category->update($request->only(['name', 'slug']));
		return response()->json(['success' => true]);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(ShopCategory $category)
	{
		if (!\Perm::has('shop@delete'))
			return response()->json(['success' => false], 403);

		$category->delete();
	}
}
