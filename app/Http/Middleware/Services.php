<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Services
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next, $access = 'default')
	{
		$tokens = [
			'default' => '1234567890',
			'matchs' 	=> '1234567890!'
		];

		if ($request->input('token') !== $tokens[$access]) {
			return response()->json([
				'status' => 'error',
				'error' => 'unauthorized'
			], 401);
		}

		return $next($request);
	}
}
