<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TournMatchUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
			Schema::table('tournaments_matches', function ($table) {
				$table->bigInteger('tournament_id')->unsigned()->nullable()->change();
				$table->bigInteger('grid_id')->unsigned()->nullable()->change();
				$table->string('code')->nullable()->unique()->change();
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
