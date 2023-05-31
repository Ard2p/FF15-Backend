<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
	protected $table = 'banners';

	protected $fillable = [
		'title', 'img', 
		'btn_name', 'btn_link',
		'game', 'category', 'status'
	];
}