<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TeamsData extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('teams', function (Blueprint $table) {
			$table->id();

			$table->string('avatar')->default('0');
			$table->string('name');
			$table->string('tag');

			$table->string('game');
			$table->integer('mmr');

			$table->string('status');
			$table->string('code')->unique();

			$table->timestamps();
		});

		Schema::create('teams_members', function (Blueprint $table) {
			$table->id();

			$table->bigInteger('team_id')->unsigned();
			$table->bigInteger('user_id')->unsigned();

			$table->string('status');

			$table->timestamps();

			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->change();
			$table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade')->change();
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('teams');
		Schema::dropIfExists('teams_members');
	}
}
