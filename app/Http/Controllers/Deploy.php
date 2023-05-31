<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Carbon\Carbon;

class Deploy extends Controller
{
	protected $webhookUrl = 'https://discord.com/api/webhooks/790489834455171113/1bGR1wNCLASS7KN2BkXRDtdnmz7Jzq5pm1xufi7J4mgUu6tyKPuYs3qkNkr6lYPDyByJ';

	function check(Request $request, $token)
	{
		if ($token != config('deploy.token'))
			return response()->json(['success' => false, 'code' => 'deploy.not_valid_token'], 403);
		return response()->json(['success' => true, 'data' => dirname(__FILE__)]);
	}

	function start(Request $request, $token)
	{
		if ($token != config('deploy.token'))
			return response()->json(['success' => false, 'code' => 'deploy.not_valid_token'], 403);

		if (!$request->has(['push', 'actor', 'repository']))
			return response()->json(['success' => false, 'code' => 'deploy.no_date'], 403);


		$actor = $request->input('actor');

		if (config('deploy.author') && $actor['uuid'] != config('deploy.author'))
			return response()->json(['success' => false, 'code' => 'deploy.no_access_author'], 403);


		$repository = $request->input('repository.uuid');

		if ($repository == config('deploy.backend_repo'))
			$deploy_path =  'cd ' . config('deploy.backend_path');
		else
		if ($repository == config('deploy.frontend_repo'))
			$deploy_path =  'cd ' . config('deploy.frontend_path');
		else
			return response()->json(['success' => false, 'code' => 'deploy.no_match_repo'], 403);


		$push = $request->input('push.changes.0.new');

		if (!$push || $push['type'] != 'branch')
			return response()->json(['success' => false, 'code' => 'deploy.no_match_push'], 403);

		if ($push['name'] != config('deploy.branch'))
			return response()->json(['success' => false, 'code' => 'deploy.no_match_branch'], 403);

		$deploy = 'php ~/.config/composer/vendor/bin/dep';

		$raw_shell = shell_exec("{$deploy_path} && {$deploy} deploy --branch " . config('deploy.branch'));
		$success = preg_match('/Successfully deployed!/', $raw_shell);
		// $raw_shell = substr($raw_shell, strpos($raw_shell, 'npm:build'));
		// $success = preg_match('/Successfully deployed!/', $raw_shell);
		// $success = false;

		opcache_reset();

		$color = $success ? 0x16b201 : 0xff0000;

		$branch = [
			'master' => 'Сервер: Разработка',
			'test' => 'Сервер: Тестовый',
			'relaese/live' => 'Сервер: Живой'
		];

		// $content  = '**Комментарий**\r\n';
		// $content .= $request->input('push.changes.0.new.target.message');

		$client = new Client();

		$hook_data = [
			'headers' => [
				'Content-Type' => 'application/json'
			],
			'json' => [
				'username' => 'Деплой',
				'avatar_url' => '',
				'embeds' => [
					[
						// 'title' => "{$request->input('repository.name')}: {$request->input('push.changes.0.new.name')}",
						'title' => '<a:S3ChikaWink:583585238332997651> ' . $branch[config('deploy.branch')],
						// "url" => $request->input('repository.links.html.href'),
						'color' => $color,
						'description' => !$success ? $raw_shell : '',
						'timestamp' => $request->input('push.changes.0.new.target.date'),
						'author' => [
							'name' => "{$actor['display_name']} ({$actor['nickname']})",
							'icon_url' => $actor['links']['avatar']['href'],
							// 'url' =>  $request->input('push.changes.0.new.target.author.user.links.html.href')
						],
						'thumbnail' => [
							'url' => 	$success ?
								'http://bourkewaste.ie/wp-content/uploads/2014/06/tick-40143_1280.png' :
								'https://cdn.discordapp.com/attachments/714998539867914271/787251934942986261/kisspng-computer-icons-clip-art-crossbones-cliparts-5b52921beb3629.8878202915321380119634.png'
						],
						'footer' => [
							"text" => $request->input('repository.name'),
							"icon_url" => $request->input('repository.links.avatar.href')
						],
						'fields' => [
							[
								'name' => 'Коментарий:',
								'value' => $request->input('push.changes.0.new.target.message')
							],
							[
								'name' => 'Время:',
								'value' => Carbon::now()->format('d.m.Y H:i:s'),
								'inline' => true
							]
							// [
							// 	'name' => 'Коммитов:',
							// 	'value' => count($request->input('push.changes')),
							// 	'inline' => true
							// ],
							// [
							// 	'name' => 'Ветвь:',
							// 	'value' => "[{$request->input('push.changes.0.new.name')}](http://example.com)",
							// 	'inline' => true
							// ]
						]
					]
				]
			]
		];

		// $result = $client->post($this->webhookUrl, $hook_data);
		return response()->json(['success' => true]);
	}

	function opcache(Request $request, $token)
	{
		if ($token != config('deploy.token'))
			return response()->json(['success' => false, 'code' => 'deploy.not_valid_token'], 403);

		opcache_reset();
		return response()->json(['success' => true]);
	}
}
