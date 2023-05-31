<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class opcachereset extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'opcache:reset';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Clear OpCache';

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
	 * @return mixed
	 */
	public function handle()
	{
		// $client = new Client;
		// $request = $client->createRequest('GET', config('app.url'));
		// $request->setPath('/deploy/' . config('deploy.token') . '/opcache');

		// $response = $client->send($request);

		$response = Http::get(config('app.url') . '/deploy/' . config('deploy.token') . '/opcache');
		// if (($response->json()['success']))
		// 	$this->line('So far, so good.');
		// else
		// 	$this->line('Ooops!');
		// \Artisan::call('db:wipe');
	}
}
