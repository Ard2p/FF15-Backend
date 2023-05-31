<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Users extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function (Blueprint $table) {
			$table->id();


			$table->string('role')->default('user');
			$table->enum('status', ['ban', 'activ', 'wait'])->default('activ');			
			$table->bigInteger('exp')->unsigned()->default(0);
			$table->string('avatar')->nullable()->default('0');

			$table->text('note')->nullable();		
			$table->string('referrer')->nullable();		
			$table->string('ref_code')->unique()->nullable();
			
			$table->string('email')->unique()->nullable();
			$table->timestamp('email_verified_at')->nullable();

			$table->string('password')->nullable();

			$table->rememberToken();
			$table->timestamps();
			
			// $table->foreign('referrer')->references('id')->on('users');
		});

		Schema::create('users_socials', function (Blueprint $table) {
			$table->id();

			$table->bigInteger('user_id')->unsigned();

			$table->string('provider_user_id');
			$table->string('provider');

			$table->timestamps();

			$table->foreign('user_id')->references('id')->on('users');
		});

		Schema::create('password_resets', function (Blueprint $table) {
			$table->string('email')->index();
			$table->string('token');

			$table->timestamp('created_at')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('users');
		Schema::dropIfExists('users_socials');
		Schema::dropIfExists('password_resets');	
	}
}
