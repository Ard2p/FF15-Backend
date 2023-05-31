<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class CheckReferral
{
  public function handle($request, Closure $next)
  {
    $response = $next($request);

    if (!session('ref_code') && $request->query('ref'))
      session(['ref_code' => $request->query('ref')]);

    return $response;
  }
}
