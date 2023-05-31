<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\USocials;
use App\Models\TPlayers;

use App\Http\Controllers\Controller;
use App\Exceptions\EmailTakenException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Hash;

use App\Mail\VerificationEmail;
use App\Mail\PasswordResetEmail;

class LoginController extends Controller
{

  public function __construct()
  {
    $this->middleware('cookies');
  }

  public function register(Request $request)
  {
    $messages = [
      'required' => 'required',
      'unique' => 'unique',
      'string' => 'string',
      'email' => 'email',
      'min' => 'min::min',
      'max' => 'max::max'
    ];
    $validator = \Validator::make($request->all(), [
      'email' => 'required|email|max:50|unique:users',
      'password' => 'required|string|min:6|max:30',
    ], $messages);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'errors' => $validator->errors()->messages()
      ], 200);
    }

    $ref 			= $request->get('ref');
    $referrer = $ref ? User::where('ref_code', $ref)->first() : null;
    $referrer = $referrer ? $referrer->id : null;

    $data = array_merge(
      $validator->validated(),
      [
        'password'  => \Hash::make($request->password),
        'ref_code'  => \Str::uuid(),
        'referrer'  => $referrer,
        'avatar'    => '0',
        'exp'       => 1000
      ]
    );

    $user = User::create($data);

    \Mail::to($user->email)->send(new VerificationEmail($user));

    if (!$token = auth()->attempt($request->only('email', 'password'))) {
      return response()->json([
        'status' => 'error',
        'error' => 'unauthorized'
      ], 401);
    }

    return response()->json([
      'status' => 'success'
    ], 201);
  }

  public function login(Request $request)
  {
    if (!$token = auth()->attempt($request->only('email', 'password'))) {
      return response()->json([
        'status' => 'error',
        'error' => 'login_wrong'
      ], 401);
    }

    $this->responseToken($token);
    return response()
      ->json([
        'status' => 'success',
        'user' => auth()->user(),
      ], 200);
  }

  public function verify(Request $request)
  {
    if (!$request->get('email') || !$request->get('token'))
      return response()->json([
        'success' => false,
        'code'    => 'verify.empty'
      ]);


    $user = User::where('email', $request->get('email'))->first();
    // dd($request->get('email'), $user);
    if (!$user)
      return response()->json([
        'success' => false,
        'code'    => 'verify.user_not_found'
      ]);

    $hash = sha1(config('app.key') . '|' . $user->getEmailForVerification());
    if (!$hash)
      return response()->json([
        'success' => false,
        'code'    => 'verify.hash_mismatch'
      ]);

    if ($user->hasVerifiedEmail())
      return response()->json([
        'success' => false,
        'code'    => 'verify.already'
      ]);

    if ($user->markEmailAsVerified())
      return response()->json([
        'success' => true,
        'code'    => 'verify.verifed'
      ]);

    return response()->json([
      'success' => false,
      'code'    => 'verify.error_undefined'
    ]);
  }

  public function resendVerify(Request $request)
  {
    $user = User::where('email', $request->get('email'))->first();
    if ($user)
      return response()->json([
        'success' => false,
        'code'    => 'verify.user_not_found'
      ]);

    if ($user->hasVerifiedEmail())
      return response()->json([
        'success' => false,
        'code'    => 'verify.already'
      ]);

    \Mail::to($user->email)->send(new VerificationEmail($user));

    return response()->json(['success' => true]);
  }

  public function passwordReset(Request $request)
  {
    $user = User::where('email', $request->get('email'))->first();
    if (!$user)
      return response()->json([
        'success' => false,
        'code'    => 'auth.user_not_found'
      ]);

    $random = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$%^&!$%^&');
    $new_password = substr($random, 0, 16);

    $update = $user->update(['password' => \Hash::make($new_password)]);
    if (!$update)
      return response()->json([
        'success' => false,
        'code'    => 'auth.error_password_update'
      ]);

    \Mail::to($user->email)->send(new PasswordResetEmail($user, $new_password));

    return response()->json(['success' => true]);
  }

  protected function responseToken($token)
  {
    Cookie::queue('token', $token, config('jwt.ttl'), '/', null, true, true, false, 'lax');
  }
}
