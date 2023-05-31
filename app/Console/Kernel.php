<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Http\Controllers\Api\v1\Statistics\StatisticsController;
use App\Http\Controllers\Api\v1\Tournaments\MatchesController;
use App\Http\Controllers\Api\v1\Tournaments\CallbackController;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		//
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		// $schedule->command('recalc:rating')->everyMinute();

		$schedule->call(function () {
			$сontroller = new	StatisticsController();
			$сontroller->recalc();
		})->everyMinute();

		$schedule->call(function () {
			$сontroller = new	CallbackController();
			$сontroller->recalc();
		})->everyMinute();

		$schedule->call(function () {
			$сontroller = new	CallbackController();
			$сontroller->recalc2();
		})->everyMinute();

		// $schedule->call(function () {
		// 	$сontroller = new	MatchesController();
		// 	$сontroller->check();
		// })->everyMinute();

		// ->everyThreeHours();
		// ->everySixHours();
	}

	/**
	 * Register the commands for the application.
	 *
	 * @return void
	 */
	protected function commands()
	{
		$this->load(__DIR__ . '/Commands');

		require base_path('routes/console.php');
	}
}
