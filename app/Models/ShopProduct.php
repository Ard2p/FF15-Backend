<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopProduct extends Model
{
	protected $table = 'shop_products';

	protected $fillable = [
		'name', 'slug','desc', 'img', 'price',
		'category', 'type','duration', 'quantity', 'status'
	];

	public function category()
	{
		return $this->hasOne(Tournament::class, 'id', 'category');
	}
}
