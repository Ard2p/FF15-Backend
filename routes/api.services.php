<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Авторизованные
Route::name('services.')
	->middleware(['services'])
	->group(function () {

		Route::get('matches',                       		'Api\v1\Tournaments\MatchesController@getUpdateList')->name('matches.index');
		Route::post('matches',                       		'Api\v1\Tournaments\MatchesController@saveStatus')->name('matches.save');

		Route::get('tournaments',                       'Api\v1\Tournaments\TournamentsController@serviceIndex')->name('tournaments.index');
	});


// Route::name('services.')
// 	->group(function () {

// 	});
