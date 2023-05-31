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
Route::name('admin.')
	->middleware(['auth:api'])
	->group(function () {
		// Route::apiResource('users',                           'Api\v1\Profiles\AdminController');
		Route::apiResource('banners',                         'Api\v1\Banners\AdminController');
		Route::apiResource('tournaments',                     'Api\v1\Tournaments\AdminController');

		Route::apiResource('shop/product',                    'Api\v1\Shop\AdminProductController');
		Route::apiResource('shop/category',                   'Api\v1\Shop\AdminCategoryController');

		Route::get('recalc/rating',                           'Api\v1\Tournaments\CallbackController@recalc')->name('recalc.rating');
		Route::get('recalc/rating2',                          'Api\v1\Tournaments\CallbackController@recalc2')->name('recalc.rating2');
		Route::get('recalc/statistics',                       'Api\v1\Statistics\StatisticsController@recalc')->name('recalc.statistics');

		Route::get('matches/check',                       		'Api\v1\Tournaments\MatchesController@check')->name('matches.check');

		Route::get('helpers/puuid',                       		'Helpers\PUUID@index')->name('helpers.puuid');
	});

// Route::post('deploy/{token}',                             'Deploy@start')->name('deploy.start');
// Route::get('deploy/{token}/check',                        'Deploy@check')->name('deploy.check');
// Route::get('deploy/{token}/opcache',                      'Deploy@opcache')->name('deploy.opcache');



// Route::get('/mail', function () {
//   $invoice = App\Models\User::find(1);
//   return (new App\Notifications\VerifyNotification($invoice))
//     ->toMail($invoice->id);
// });

// Route::get('/mail', function () {
//   $user = App\Models\User::find(101);
//   \Mail::to($user->email)->queue(new App\Mail\VerificationEmail($user));
//   return config('app.key');
// });
