<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logging extends Model
{
	protected $table = 'logging';

	protected $fillable = ['name', 'type', 'log'];
}
