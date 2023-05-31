<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TGrids extends Model
{
	protected $table = 'tournaments_grids';

	public $timestamps = false;

	protected $dates = ['prepare_at'];

	protected $fillable = [
		'tournament_id', 'num',
		'round', 'grid', 'bo',
		'win', 'team1', 'team2',
		'team1_score', 'team2_score', 'prepare_at'
	];

	public function matches()
	{
		// $class = TMatches::class;
		// $class::$staticMakeVisible = ['code'];

		return $this->hasMany(TMatches::class, 'grid_id', 'id');
	}

	public function tournament()
	{
		return $this->hasOne(Tournament::class, 'id', 'tournament_id');
	}
}
