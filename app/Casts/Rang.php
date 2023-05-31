<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Rang implements CastsAttributes
{
	/**
	 * Cast the given value.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @param  string  $key
	 * @param  mixed  $value
	 * @param  array  $attributes
	 * @return mixed
	 */
	public function get($model, $key, $value, $attributes)
	{
		$league = null;
		$division = null;

		foreach (config('games.lol.leagues') as $league_key => $divisions) {
			$divisions_key = array_reverse(array_keys($divisions));
			foreach ($divisions_key as $division_key) {
				$elo = $divisions[$division_key];

				if ($value >= $elo) {
					$league		= $league_key;
					$division = count($divisions_key) > 1 ? $division_key : null;
				} else return [
					'league'		=> $league,
					'division'	=> $division
				];
			}			
		}
		return [
			'league'		=> $league,
			'division'	=> $division
		];
	}

	/**
	 * Prepare the given value for storage.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @param  string  $key
	 * @param  mixed  $value
	 * @param  array  $attributes
	 * @return mixed
	 */
	public function set($model, $key, $value, $attributes)
	{
		return $value;
	}
}
