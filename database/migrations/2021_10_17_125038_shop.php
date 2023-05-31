<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Shop extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
			Schema::create('shop_categories', function (Blueprint $table) {
				$table->id();

				$table->string('name');
				$table->string('slug')->unique();

				$table->timestamps();

				// $table->foreign('referrer')->references('id')->on('users');
			});

			Schema::create('shop_products', function (Blueprint $table) {
				$table->id();

				$table->string('name');
				$table->string('slug')->unique();
				$table->text('desc')->nullable();
				$table->string('img')->nullable();
				$table->integer('price');
				$table->bigInteger('category')->nullable();
				$table->string('type');
				$table->integer('duration')->nullable();
				$table->integer('quantity');

				$table->string('status');
				$table->timestamps();

				// $table->foreign('referrer')->references('id')->on('users');
			});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
			Schema::dropIfExists('shop_categories');
			Schema::dropIfExists('shop_products');
    }
}
