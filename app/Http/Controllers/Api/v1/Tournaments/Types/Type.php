<?php

namespace App\Http\Controllers\Api\v1\Tournaments\Types;

class Type
{
	public function getGameClass($game)
	{
		$class = 'App\Http\Controllers\Api\v1\Tournaments\Games\\' . config('games.tournaments.games.' . $game);
		if (!class_exists($class))
			return response()->json(['success' => false, 'code' => 'tournament.not_game_class'])->send();
		return new $class();
	}
}
