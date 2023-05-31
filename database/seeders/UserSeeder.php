<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		$count = 1;
		$users = [
			1 => ['role' => 'admin', 'provider' => 'vkontakte', 'provider_user_id' => '139969201'],
			2 => ['role' => 'admin', 'provider' => 'vkontakte', 'provider_user_id' => '25811889']
		];

		foreach ($users as $id => $user) {
			DB::table('users')->insert([
				'exp' 			=> 2250,
				'role'			=> $user['role'],
				'ref_code' 	=> \Str::uuid()
			]);

			DB::table('users_socials')->insert([
				'user_id' 					=> $id,
				'provider' 					=> $user['provider'],
				'provider_user_id'	=> $user['provider_user_id']
			]);

			DB::table('games_accounts')->insert([
				'user_id'		=> $id,
				'game'			=> 'lol',
				'nickname'	=> 'Player_' . Str::random(5),
				'profileId'	=> Str::random(10),
				'accountId'	=> Str::random(10),
				'active'		=> true
			]);

			DB::table('games_profiles')->insert([
				'user_id' 	=> $id,
				'game' 			=> 'lol',
				'mmr' 			=> 1500,
				'priority'	=> 10,
				'roles'			=> '["top", "mid",	"jung", "adc",	"sup"]'
			]);

			DB::table('tournaments')->insert([
				'user_id' => $id,
				'name' 		=> 'Название ' . $id,
				'game' 		=> 'lol',
				'type' 		=> 'rtc',
				'round'		=> 1,
				'start' 	=> new \DateTime(),
				'status' 	=> 'open'
			]);

			DB::table('tournaments_players')->insert([
				'tournament_id'	=> 1,
				'user_id' 			=> $id,
				'account_id'  	=> $id
			]);

			$count++;
		}

		DB::table('tournaments')->insert([
			'user_id' => 1,
			'name' 		=> 'Название 3',
			'game' 		=> 'lol',
			'type' 		=> 'rtc',
			'round'		=> 1,
			'start' 	=> new \DateTime(),
			'status' 	=> 'open'
		]);

		DB::table('tournaments')->insert([
			'user_id' => 2,
			'name' 		=> 'Название 4',
			'game' 		=> 'lol',
			'type' 		=> 'rtc',
			'round'		=> 1,
			'start' 	=> new \DateTime(),
			'status' 	=> 'open'
		]);

		DB::table('banners')->insert([
			'title'			=> 'Тест баннер',
			'img' 			=> 'https://dev-rx78-3.ff15.ru/img/banners/b5.png',
			'btn_name'	=> 'Играть',
			'btn_link'	=> '/tournaments/1',
			'game'  		=> 'lol'
		]);

		for ($i = $count; $i <= 100 + $count; $i++) {
			DB::table('users')->insert([
				'exp' => 2250
			]);

			DB::table('games_accounts')->insert([
				'user_id'		=> $i,
				'game'			=> 'lol',
				'nickname'	=> 'Player_' . Str::random(5),
				'profileId'	=> Str::random(10),
				'accountId'	=> Str::random(10),
				'active'		=> true
			]);

			DB::table('games_profiles')->insert([
				'user_id'		=> $i,
				'game'			=> 'lol',
				'mmr'				=> 1500,
				'priority'	=> 10,
				'roles'  		=> '["top", "mid",	"jung", "adc",	"sup"]'
			]);

			DB::table('tournaments_players')->insert([
				[
					'tournament_id'	=> 1,
					'user_id' 			=> $i,
					'account_id'  	=> $i,
					'round'  				=> 1
				], [
					'tournament_id'	=> 1,
					'user_id' 			=> $i,
					'account_id'  	=> $i,
					'round'  				=> 2
				], [
					'tournament_id'	=> 1,
					'user_id' 			=> $i,
					'account_id'  	=> $i,
					'round'  				=> 3
				], [
					'tournament_id'	=> 2,
					'user_id' 			=> $i,
					'account_id'  	=> $i,
					'round'  				=> 1
				], [
					'tournament_id'	=> 3,
					'user_id' 			=> $i,
					'account_id'  	=> $i,
					'round'  				=> 1
				], [
					'tournament_id'	=> 4,
					'user_id' 			=> $i,
					'account_id'  	=> $i,
					'round'  				=> 1
				]
			]);
		}
	}
}
