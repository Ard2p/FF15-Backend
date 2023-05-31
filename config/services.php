<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */


	'rso' => [
		'client_id' => env('RSO_KEY'),
		'client_secret' => env('RSO_SECRET'),
		'redirect' => env('RSO_REDIRECT_URI')
	],

	'vkontakte' => [
		'client_id' => env('VKONTAKTE_KEY'),
		'client_secret' => env('VKONTAKTE_SECRET'),
		'redirect' => env('VKONTAKTE_REDIRECT_URI')
	],

	'discord' => [
		'client_id' => env('DISCORD_KEY'),
		'client_secret' => env('DISCORD_SECRET'),
		'redirect' => env('DISCORD_REDIRECT_URI')
	],

	'twitch' => [
		'client_id' => env('TWITCH_KEY'),
		'client_secret' => env('TWITCH_SECRET'),
		'redirect' => env('TWITCH_REDIRECT_URI')
	],

	'mailgun' => [
		'domain' => env('MAILGUN_DOMAIN'),
		'secret' => env('MAILGUN_SECRET'),
		'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
	],

	'postmark' => [
		'token' => env('POSTMARK_TOKEN'),
	],

	'ses' => [
		'key' => env('AWS_ACCESS_KEY_ID'),
		'secret' => env('AWS_SECRET_ACCESS_KEY'),
		'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
	],

];
