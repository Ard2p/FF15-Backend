<?php

use RiotAPI\LeagueAPI\LeagueAPI;

return [
	'allows' 			=> ['lol'],

	'tournaments'	=> [
		'types' => [

			'rtc' 		=> 'RTC',

			'rtc_se' 	=> 'RTC_SE',
			// 'rtc_de' 	=> 'RTC_DE',

			// 'rtc_1x1' => 'RTC_1X1',
			// 'rtc_2x2' => 'RTC_2X2',

			'team' 		=> 'TEAM_SE',
			'team_de' => 'TEAM_DE',

			// 'team_1x1' => 'TEAM_1X1',
			// 'team_2x2' => 'TEAM_2X2',

		],
		'games' => ['lol' => 'LoL']
	],

	'lol' => [
		'api_config' => [
			LeagueAPI::SET_REGION           => 'ru',
			LeagueAPI::SET_KEY              => env('RIOT_KEY'),
			LeagueAPI::SET_TOURNAMENT_KEY   => env('RIOT_TKEY'),
			LeagueAPI::SET_VERIFY_SSL       => env('APP_ENV') == 'dev'  ? false : true,
			LeagueAPI::SET_INTERIM          => env('APP_ENV') == 'live' ? false : true,
			LeagueAPI::SET_CACHE_RATELIMIT	=> false,
			LeagueAPI::SET_CACHE_CALLS      => false
		],

		'provider'			=> env('RIOT_PROVIDER'),
		'redirect'			=> env('RIOT_REDIRECT_URI'),
		'token'					=> 'AAe%xFeMRMEByo8NuQiXaksmny#T4G{{',

		'max_elo' 			=> 3200,
		'min_elo' 			=> 1300,

		'leagues'				=> [
			'iron'				=> [1 => 400,   2 => 300,   3 => 200,   4 => 100],
			'bronze'			=> [1 => 800,   2 => 700,   3 => 600,   4 => 500],
			'silver'			=> [1 => 1200,  2 => 1100,  3 => 1000,  4 => 900],
			'gold'				=> [1 => 1600,  2 => 1500,  3 => 1400,  4 => 1300],
			'platinum'		=> [1 => 2000,  2 => 1900,  3 => 1800,  4 => 1700],
			'diamond'			=> [1 => 2400,  2 => 2300,  3 => 2200,  4 => 2100],
			'master'			=> [1 => 2600],
			'grandmaster'	=> [1 => 2800],
			'challenger'	=> [1 => 3000]
		],

		'allowedSummonerIds' => [
			'JIp8oONLwnRpQYYYR7i6sg2tTSRVr7SqU6QXhS2rE-6K', 		// Кошечка Миу
			'S-_f3FK2Y6LSYZvqSDqpvtySSkzCGGCYJOlmUnIsR69log',		// Carnage2184
			'X6fK2JAxC9RRbxeHLMafHvfQFLa_uWE6HKVmzXloNVHxKA',		// Стася
			'gpsQFd5G1TfU4ROCeiftHqGz_DoLF_cvq8DDbJW3GcWQkQ',		// Fallentale
			'Z4wMvC19nFpFUUbcFKkxgQBUZAqtXUmWz5162L_Kei7_E0I',	// AllyAllyGG
			'2I7VPR_cZ19Mdw95bff2JAP0fkYzMdViYToX5bCJPGdr',			// Better Supp Win
			'1p7rU-_RMr7tmfqUlpFCmy5T2LVhoCGZa7xA7Wy7OaYi7g',   // LØST ORION
			'UHO0a9RSr1eD9GAPO2XHSePFVkEWMcRH9DlX-nHA5jBQOU0',  // NosSIRraG
			'PurfVxhHAN_VkjbFUM7Uwm63so3HrwtDb-5hrqsE13W4_eg',	// Стaрший
			'inpfXo10zqCtOWr63ELXL68iUrqSG2ZEReDN1c6jfRCEuA',		// Млaдший
			'MHsh7R6O1txmrFWnPycBV4kzhqBpPFfZJCj0NZwjRPK1KKI'		// EquipeDeClowns
		]
	]

];
