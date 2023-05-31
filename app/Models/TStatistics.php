<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TStatistics extends Model
{
	protected $table = 'tournaments_statistics';

	protected $fillable = [
		'user_id', 'points',
		'game', 'type',
		'win', 'lose',
		'k', 'd', 'a'
	];

	public function profile()
	{
		return $this->hasOne(GameProfile::class, 'user_id', 'user_id');
	}

	public function account()
	{
		return $this->hasOne(GameAccount::class, 'user_id', 'user_id');
	}
}
