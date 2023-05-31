<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Penalty extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_penalty', function (Blueprint $table) {
            $table->id();

            $table->string('type')->default('ban');
            $table->bigInteger('user_id')->unsigned();
            $table->text('reason')->nullable();
            $table->dateTime('end');

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
        Schema::dropIfExists('users_penalty');
    }
}
