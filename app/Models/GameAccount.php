<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameAccount extends Model
{
  protected $table = 'games_accounts';

  protected $fillable = [
		'user_id', 
		'game','nickname', 
		'profileId', 'accountId', 'active'
	];
}
