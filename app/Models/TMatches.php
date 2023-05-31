<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TMatches extends Model
{
	protected $table = 'tournaments_matches';

	protected $fillable = [
		'tournament_id', 'grid_id',
		'code', 'match', 'match_id',
		'status', 'win',
		'start_at', 'end_at'
	];

	protected $dates = ['start_at', 'end_at'];

	// protected $hidden = ['code'];

	protected $casts = [
		'match' => 'array'
	];

	public function tournament()
	{
		return $this->hasOne(Tournament::class, 'id', 'tournament_id');
	}

	public function grid()
	{
		return $this->hasMany(TGrids::class, 'id', 'grid_id');
	}

	public function players()
	{
		return $this->hasMany(TPlayers::class, 'grid_id', 'grid_id');
	}
}
