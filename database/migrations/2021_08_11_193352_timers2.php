<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Timers2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
			Schema::table('tournaments_matches', function ($table) {
				$table->timestamp('start_at', $precision = 0)->nullable()->default(NULL)->change();
				$table->timestamp('end_at', 	$precision = 0)->nullable()->default(NULL)->change();
			});

			Schema::table('tournaments_grids', function (Blueprint $table) {
				$table->dateTime('prepare_at')->nullable()->default(NULL)->change();
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
