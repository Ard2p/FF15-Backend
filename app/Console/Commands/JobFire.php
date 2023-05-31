<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TournamentEvents;
use App\Jobs\MatchsEvents;

class JobFire extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'fire';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle()
	{
		$data = json_encode([
			'action' 		=> 'update',
			'match'			=> [
				'id' 			=> 2,
				'code' 		=> 'RU04931-3f231cf3-c0be-4611-86f8-80e6818b53ee',
				'status' 	=> 'process'
			]
		]);

		MatchsEvents::dispatch($data);

		// TournamentEvents::dispatch($data)->onQueue('matchs');
	}
}
