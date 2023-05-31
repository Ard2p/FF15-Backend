<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
	protected $table = 'teams';

	protected $fillable = [
		'avatar', 'name', 'tag', 'game',
		'mmr', 'status', 'code'
	];

	public function members()
	{
		return $this->hasMany(TeamMember::class, 'team_id', 'id');
	}
}
