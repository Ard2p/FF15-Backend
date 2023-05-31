<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TournamentsData extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('tournaments', function (Blueprint $table) {
			$table->id();

			$table->bigInteger('user_id')->unsigned();
			$table->bigInteger('provider_id')->nullable()->unsigned();

			$table->string('name');
			$table->string('img')->nullable();
			$table->longText('desc')->nullable();
			$table->longText('prize')->nullable();

			$table->string('twitch')->nullable();
			$table->string('discord')->nullable();

			$table->string('game');
			$table->string('type');
			$table->integer('round')->default(1);

			$table->integer('lvl')->default(1);
			$table->integer('max_players')->nullable();

			$table->boolean('leave_disable')->default(0);
			$table->boolean('grid_disable')->default(0);

			$table->dateTime('start');
			$table->string('status')->enum(['create', 'pending', 'open', 'balance', 'process', 'end', 'arhive']);

			$table->timestamps();

			$table->foreign('user_id')->references('id')->on('users');
		});


		Schema::create('tournaments_grids', function (Blueprint $table) {
			$table->id();

			$table->bigInteger('tournament_id')->unsigned();

			$table->integer('round')->default(1);
			$table->string('grid')->enum(['main', 'looser', 'final']);
			$table->integer('bo')->default(1);

			$table->integer('win')->nullable();
			$table->integer('team1');
			$table->integer('team2');
			$table->integer('team1_score')->default(0);
			$table->integer('team2_score')->default(0);

			$table->foreign('tournament_id')->references('id')->on('tournaments');
		});


		Schema::create('tournaments_matches', function (Blueprint $table) {
			$table->id();

			$table->bigInteger('grid_id')->unsigned();
			$table->bigInteger('tournament_id')->unsigned();

			$table->string('status')->enum(['wait', 'battle', 'end', 'success'])->default('wait');

			$table->integer('win')->nullable();

			$table->string('code')->nullable()->unique();
			$table->json('match')->nullable();

			$table->timestamps();

			$table->foreign('tournament_id')->references('id')->on('tournaments');
			$table->foreign('grid_id')->references('id')->on('tournaments_grids')->onDelete('cascade');
		});


		Schema::create('tournaments_players', function (Blueprint $table) {
			$table->id();

			$table->bigInteger('tournament_id')->unsigned()->nullable();
			$table->bigInteger('grid_id')->unsigned()->nullable();
			// $table->bigInteger('team_id')->unsigned()->nullable();

			$table->bigInteger('user_id')->unsigned()->nullable();
			$table->bigInteger('account_id')->unsigned()->nullable();

			$table->string('role')->nullable();
			$table->integer('team')->nullable();
			$table->integer('round')->default(1);

			$table->timestamps();

			// $table->foreign('team_id')->references('id')->on('users');
			$table->foreign('user_id')->references('id')->on('users');
			$table->foreign('account_id')->references('id')->on('games_accounts');
			$table->foreign('tournament_id')->references('id')->on('tournaments');
			$table->foreign('grid_id')->references('id')->on('tournaments_grids')->onDelete('set null');
		});


		Schema::create('tournaments_statistics', function (Blueprint $table) {
			$table->id();

			$table->bigInteger('user_id')->unsigned();

			$table->string('type')->default('default');
			$table->string('game');
			$table->string('win')->default(0);
			$table->string('lose')->default(0);
			$table->integer('k')->default(0);
			$table->integer('d')->default(0);
			$table->integer('a')->default(0);
			$table->float('points')->default(0);

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
		Schema::dropIfExists('tournaments');
		Schema::dropIfExists('tournaments_grids');
		Schema::dropIfExists('tournaments_matches');
		Schema::dropIfExists('tournaments_players');
		Schema::dropIfExists('tournaments_statistics');
	}
}
