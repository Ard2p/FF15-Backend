<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
	protected $fillable = [
		'user_id', 'provider_id',
		'name', 'img', 'desc', 'prize',
		'twitch', 'discord',
		'game', 'type', 'round',
		'lvl', 'max_players',
		'leave_disable', 'grid_disable',
		'start', 'status'
	];

	protected $casts = [
		'leave_disable' => 'boolean',
		'grid_disable'  => 'boolean'
	];

	protected $dates = ['start'];

	public function players()
	{
		return $this->hasMany(TPlayers::class, 'tournament_id', 'id');
	}

	public function grids()
	{
		return $this->hasMany(TGrids::class, 'tournament_id', 'id');
	}

	public function matches()
	{
		return $this->hasMany(TMatches::class);
	}
}
