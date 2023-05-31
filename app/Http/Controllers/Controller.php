<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use RiotAPI\LeagueAPI\LeagueAPI;

class Controller extends BaseController
{
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

	function riotAPI()
	{
		return new LeagueAPI(config('games.lol.api_config'));
	}

	public function getTypeClass($type)
	{
		$class = 'App\Http\Controllers\Api\v1\Tournaments\Types\\' . config('games.tournaments.types.' . $type);
		if (!class_exists($class))
			return response()->json(['success' => false, 'code' => 'tournament.not_type_class'])->send();
		return new $class();
	}

	public function getGameClass($game)
	{
		$class = 'App\Http\Controllers\Api\v1\Tournaments\Games\\' . config('games.tournaments.games.' . $game);
		if (!class_exists($class))
			return response()->json(['success' => false, 'code' => 'tournament.not_game_class'])->send();
		return new $class();
	}
}
