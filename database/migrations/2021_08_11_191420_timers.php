<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Timers extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('tournaments_matches', function ($table) {
			$table->dateTime('start_at')->nullable()->default(NULL);
			$table->dateTime('end_at')->nullable()->default(NULL);
		});

		Schema::table('tournaments_grids', function (Blueprint $table) {
			$table->dateTime('prepare_at')->nullable()->default(NULL);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
	}
}
