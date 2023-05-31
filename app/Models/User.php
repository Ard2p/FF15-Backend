<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;

use App\Models\GameAccount;
use App\Models\GameProfile;

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
	use Notifiable;

	// protected $with = ['account_lol'];

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'avatar', 'exp', 'note',
		'role', 'status',
		'email', 'password',
		'referrer', 'ref_code'
	];

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = ['password', 'email_verified_at', 'email'];

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = ['email_verified_at' => 'datetime'];

	public function socials()
	{
		return $this->hasMany(USocials::class);
	}

	public function accounts()
	{
		return $this->hasMany(GameAccount::class);
	}

	public function profiles()
	{
		return $this->hasMany(GameProfile::class);
	}

	public function getJWTIdentifier()
	{
		return $this->getKey();
	}

	public function getJWTCustomClaims()
	{
		return [];
	}

	public function getPermissions()
	{
		$this->permissions = config('permissions.roles.' . $this->role);
	}

	public function statistics()
	{
		return $this->hasOne(TStatistics::class, 'user_id', 'user_id');
	}
	
	public function profile()
	{
		return $this->hasOne(GameProfile::class, 'user_id', 'user_id');
	}
}
