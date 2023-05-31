<?php

namespace App\Helpers;

use App\Models\UPenalty;
use Carbon\Carbon;

class Permissions
{
  protected $id = null;
  protected $role = null;
  protected $permissions = [];

  public function __construct()
  {
    if (\Auth::check()) $this->setUser(\Auth::user());
  }

  public function allows($permission, $item = null)
  {
		if (!\Auth::check()) return false;

    $penalty = UPenalty::where('user_id', \Auth::user()->id)
      ->where('type', 'ban')
      ->where('end', '>', Carbon::now())->first();
    if ($penalty)
      return response()->json(['success' => false, 'code' => 'user.banned', 'penalty' => $penalty], 403);

    return $this->has($permission) || $this->self($permission . '-self', $item);
  }

  public function denies($permission, $item = null)
  {
    return !$this->allows($permission, $item);
  }

  public function has($permission)
  {
    return in_array($permission, $this->permissions);
  }

  public function self($permission, $item)
  {
    if (!$item || gettype($item) != 'object' || !$item->user_id) return false;
    return $this->has($permission) && $this->id == $item->user_id;
  }

  public function role(array $roles)
  {
    return in_array($this->role, $roles);
  }

  public function setUser($user)
  {
    $this->id = $user->id;
    $this->role = $user->role;
    $this->permissions = config('permissions.roles.' . $this->role);
  }
}
