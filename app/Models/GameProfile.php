<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Casts\Rang;

class GameProfile extends Model
{
	protected $table = 'games_profiles';

	protected $fillable = [
		'user_id', 'game',
		'mmr', 'priority', 'roles', 'champions'
	];

	protected $casts = [
		'roles' => 'array',
		'mmr'		=> Rang::class,
		'match' => 'array',
		'champions' => 'array',
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function accounts()
	{
		return $this->hasMany(GameAccount::class, 'user_id', 'user_id');
	}

	public function statistics()
	{
		return $this->hasOne(TStatistics::class, 'user_id', 'user_id');
	}
}
