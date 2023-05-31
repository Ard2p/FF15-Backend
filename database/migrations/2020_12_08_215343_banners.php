<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Banners extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('banners', function (Blueprint $table) {
			$table->id();

			$table->string('title');
			$table->string('img');

			$table->string('btn_name')->nullable();
			$table->string('btn_link')->nullable();

			$table->string('game')->nullable();
			$table->string('category')->default('default');

			$table->string('status')->default('publish');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('banners');
	}
}
