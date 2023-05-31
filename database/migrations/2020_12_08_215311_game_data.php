<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GameData extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('games_accounts', function (Blueprint $table) {
			$table->id();

			$table->bigInteger('user_id')->unsigned();

			$table->string('game');
			$table->string('nickname')->nullable();
			$table->string('profileId')->nullable();
			$table->string('accountId')->nullable();
			$table->boolean('active')->default(false);
			
			$table->softDeletes();
			$table->timestamps();

			$table->foreign('user_id')->references('id')->on('users');
		});

		Schema::create('games_profiles', function (Blueprint $table) {
			$table->id();

			$table->bigInteger('user_id')->unsigned();

			$table->string('game');
			$table->integer('mmr')->unsigned()->nullable();	
			$table->string('priority')->default(0);
			$table->json('roles')->nullable();

			$table->timestamps();

			$table->foreign('user_id')->references('id')->on('users');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('games_accounts');
		Schema::dropIfExists('games_profiles');
	}
}
