<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TPlayers extends Model
{
	protected $table = 'tournaments_players';

	protected $fillable = [
		'tournament_id', 'grid_id',
		'user_id', 'team_id', 'account_id',
		'role', 'team', 'round', 'status'
	];

	public function statistics()
	{
		return $this->hasOne(TStatistics::class, 'user_id', 'user_id');
	}

	public function profile()
	{
		return $this->hasOne(GameProfile::class, 'user_id', 'user_id');
	}

	public function member()
	{
		return $this->hasOne(TeamMember::class, 'user_id', 'user_id');
	}

	public function members()
	{
		return $this->hasMany(TeamMember::class, 'team_id', 'team_id');
	}
}
