<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
	protected $table = 'teams_members';

	protected $fillable = [
		'team_id', 'user_id', 'status'
	];
}
