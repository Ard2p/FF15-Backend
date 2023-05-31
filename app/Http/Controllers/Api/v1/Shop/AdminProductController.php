<?php

namespace App\Http\Controllers\Api\v1\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\ShopProduct;

class AdminProductController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$products = ShopProduct::all();
		return response()->json($products);
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
			'slug'			=> 'required|string|unique:shop_products,slug',
			'desc'      => 'nullable|string',
			'img'   	  => 'nullable|string',
			'price'			=> 'required|integer',
			'category'  => 'nullable|integer',
			'type'      => 'required|string',
			'duration'	=> 'nullable|integer',
			'quantity'	=> 'required|integer',
			'status'    => 'required|in:draft,publish,outofstock,onsalesoon,onrequest'
		], $messages);

		if ($validator->fails())
			return response()->json(['success' => false, 'validator' => $validator->errors()->messages()]);

		$product = ShopProduct::create($request->all());
		return response()->json($product);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show(ShopProduct $product)
	{
		return response()->json($product);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, ShopProduct $product)
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
			'slug'			=> 'required|string|unique:shop_products,slug,' . $product->slug,
			'desc'      => 'nullable|string',
			'img'   	  => 'nullable|string',
			'price'			=> 'required|integer',
			'category'  => 'nullable|integer',
			'type'      => 'required|string',
			'duration'	=> 'nullable|integer',
			'quantity'	=> 'required|integer',
			'status'    => 'required|in:draft,publish,outofstock,onsalesoon,onrequest'
		], $messages);

		if ($validator->fails())
			return response()->json(['success' => false, 'validator' => $validator->errors()->messages()]);

		$product->update($request->all());
		return response()->json(['success' => true]);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(ShopProduct $product)
	{
		if (!\Perm::has('shop@delete'))
			return response()->json(['success' => false], 403);

		$product->delete();
	}
}
