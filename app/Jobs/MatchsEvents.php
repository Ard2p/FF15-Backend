<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MatchsEvents
{
	use Dispatchable;

	private $data;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$config = config('queue.connections.rabbitmq.hosts')[0];
		$connection = new AMQPStreamConnection(
			$config['host'],
			$config['port'],
			$config['user'],
			$config['password'],
			$config['vhost']
		);
		$channel = $connection->channel();

		// $channel->queue_declare('matchs', false, false, false, false);

		$msg = new AMQPMessage($this->data);
		$channel->basic_publish($msg, '', 'matchs');

		$channel->close();
		$connection->close();
		return true;
	}
}
