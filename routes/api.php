<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

#region Не авторизованные
Route::middleware(['guest:api'])->group(function () {
	Route::post('auth/login',                             'Auth\LoginController@login')->name('auth.login');
	Route::post('auth/register',                          'Auth\LoginController@register')->name('auth.register');
	Route::post('auth/password/reset',                    'Auth\LoginController@passwordReset')->name('auth.password.reset');
});
#endregion

#region Авторизованные
Route::middleware(['auth:api'])->group(function () {
	Route::post('auth/logout',                            'Auth\OAuthController@logout')->name('auth.logout');
	Route::post('auth/profile',                           'Auth\OAuthController@profile')->name('auth.profile');
	Route::post('auth/verify/resend',                     'Auth\LoginController@resendVerify')->name('auth.verify.resend');
	Route::delete('auth/{provider}',                      'Auth\OAuthController@destroyProvider')->name('auth.provider.destroy');

	Route::get('profile',                                 'Api\v1\Profiles\ProfileController@index')->name('profile.index');
	Route::put('profile',                                 'Api\v1\Profiles\ProfileController@update')->name('profile.update');
	Route::apiResource('profile/accounts',                'Api\v1\Profiles\AccountsController')->except('show');
	Route::get('profiles',                                'Api\v1\Profiles\ProfilesController@index')->name('profiles.index');
	Route::put('profiles/{user}',                         'Api\v1\Profiles\ProfilesController@update')->name('profiles.update');
	Route::post('tournaments/{id}/events',                'Api\v1\Tournaments\EventsController@switch')->name('tournaments.events');


	Route::apiResource('teams',                						'Api\v1\Teams\TeamsController');
	Route::post('teams/{id}/events',               				'Api\v1\Teams\TeamsController@switch')->name('teams.events');
});
#endregion

#region Для всех

#region Куски
Route::post('auth/verify',                              'Auth\LoginController@verify')->name('auth.verify');
Route::get('auth/{provider}',                           'Auth\OAuthController@redirectToProvider')->name('auth.provider.store');
Route::get('auth/{provider}/callback',                  'Auth\OAuthController@handleProviderCallback')->name('auth.provider.callback');

Route::get('banners',                                   'Api\v1\Banners\BannersController@index')->name('banners.index');
#endregion

#region Страницы
Route::get('profiles/{id}',                             'Api\v1\Profiles\ProfilesController@show')->name('profiles.show');
Route::post('profiles/search',                          'Api\v1\Profiles\ProfilesController@search')->name('profiles.search');

Route::apiResource('tournaments',                       'Api\v1\Tournaments\TournamentsController');
Route::post('tournaments/{game}/callback',              'Api\v1\Tournaments\CallbackController@switch')->name('tournaments.callback');

Route::get('shop',              												'Api\v1\Shop\ShopController@index')->name('shop.index');

Route::get('ratings',                                   'Api\v1\Ratings\RatingsController@index')->name('ratings.index');
Route::get('statistics',                                'Api\v1\Statistics\StatisticsController@index')->name('statistics.index');
#endregion

#endregion





Route::get('tournamentsrecalc2',              'Api\v1\Tournaments\CallbackController@recalc2')->name('tournaments.recalc2');
