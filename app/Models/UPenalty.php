<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UPenalty extends Model
{
	protected $table = 'users_penalty';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'user_id', 'reason', 'end', 'type'
	];

	protected $dates = ['end'];
}
