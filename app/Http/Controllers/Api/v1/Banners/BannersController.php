<?php

namespace App\Http\Controllers\Api\v1\Banners;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Banner;

class BannersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
	public function index(Request $request)
	{
        $game = 'lol';
		$banners = Banner::where('game', $game)->get();
		return $banners;
    }
}
