<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class USocials extends Model
{
    protected $table = 'users_socials';
    protected $fillable = ['user_id', 'provider_user_id' ,'provider'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
