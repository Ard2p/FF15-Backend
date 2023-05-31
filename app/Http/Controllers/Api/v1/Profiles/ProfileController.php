<?php

namespace App\Http\Controllers\Api\v1\Profiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Team;
use App\Models\UPenalty;
use App\Models\USocials;
use App\Models\TMatches;
use App\Models\TStatistics;
use App\Models\GameProfile;

use App\Mail\VerificationEmail;
use App\Http\Resources\ProfileIndex;
use App\Models\TeamMember;

class ProfileController extends Controller
{

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$game = 'lol';
		$user = \Auth::user();

		$select = [
			'id', 'id as user_id', 'role', 'status', 'exp', 'avatar', 'referrer',
			'ref_code', 'email', 'email_verified_at', 'created_at'
		];

		switch ($request->get('page')) {

			case 'settings':
				$data = User::select($select)
					->where('id', $user->id)->first()
					->makeVisible(['email_verified_at', 'email']);
				$data->socials = USocials::select('provider')
					->where('user_id', $user->id)->get()->pluck('provider');
				break;

			case 'avatars':
				$avatarsGroups = config('avatars');

				foreach ($avatarsGroups as $group => $avatarsGroup)
					if (\Perm::role($avatarsGroup['perm']))
						$data[$group] = $avatarsGroup['icons'];
				break;

			case 'team':
				$teamId =	TeamMember::select('team_id')->where('user_id', $user->id)->pluck('team_id')->first();

				$data = $teamId ? Team::select('*')
					->with([
						'members' => function ($query) use ($game) {
							$query->select(['users.id', 'avatar', 'exp', 'role', 'nickname', 'team_id', 'teams_members.status']);
							$query->join('users', function ($join) {
								$join->on('users.id', '=', 'teams_members.user_id');
							});
							$query->join('games_accounts', function ($join) use ($game) {
								$join->on('games_accounts.user_id', '=', 'teams_members.user_id');
								$join->where('games_accounts.active', true);
								$join->where('games_accounts.game', $game);
							});
						}
					])
					->find($teamId) : [];

				break;

			case 'matches':

				break;

			default:
				$data = User::select($select)->where('id', $user->id)
					->with([
						'profile' => function ($query) use ($game) {
							$query->select('id', 'user_id', 'game', 'mmr', 'roles', 'champions');
							$query->where('game', $game);
						},
						'accounts' => function ($query) use ($game) {
							$query->select('id', 'user_id', 'game', 'nickname', 'active');
							$query->where('game', $game);
						},
						'statistics' => function ($query) use ($game) {
							$query->select('user_id', 'points', 'created_at', 'win', 'lose');
							$query->where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), date('Y-m'));
							$query->where('game', $game);
							$query->where('type', 'rtc');
						}
					])->first();

				$data->rating = isset($data->statistics) ?
					TStatistics::where('points', '>', $data->statistics->points)
					->where(DB::raw('DATE_FORMAT(created_at , \'%Y-%m\')'), date('Y-m'))
					->count() + 1 : null;

				break;
		}
		return response()->json($data);
	}

	public function update(Request $request)
	{
		if (!\Perm::has('user@edit-self'))
			return response()->json(['success' => false], 403);

		switch ($request->get('update')) {
			case 'roles':
				return $this->updateRoles($request);
				break;
			case 'avatar':
				return $this->updateAvatar($request);
				break;
			case 'password':
				return $this->updatePassword($request);
				break;
			case 'email':
				return $this->updateEmail($request);
				break;
		}

		return response()->json(['success' => false, 'code' => 'profile.error_switch']);
	}

	public function updateRoles(Request $request)
	{
		$roles = $request->get('roles');
		$check_roles = ['top', 'mid', 'adc', 'sup', 'jung'];

		if (count($roles) > 5 || count($roles) < 5)
			return response()->json(['success' => false, 'code' => 'profile.wrong_roles']);

		foreach ($roles as $role)
			if (!in_array($role, $check_roles))
				return response()->json(['success' => false, 'code' => 'profile.wrong_roles']);

		if (count($roles) > 5 || count($roles) < 5)
			return response()->json(['success' => false, 'code' => 'profile.wrong_roles']);

		\Auth::user()->profiles()->update(['roles' => $roles]);
		return response()->json(['success' => true]);
	}

	public function updateAvatar(Request $request)
	{
		$category = config('avatars.' . $request->get('category') . '.icons');
		if (!$category)
			return response()->json(['success' => false, 'code' => 'avatar.category_mismatch']);

		if (!\Perm::role(config('avatars.' . $request->get('category') . '.perm')))
			return response()->json(['success' => false, 'code' => 'avatar.category_not_access']);

		$avatar = $category[$request->get('avatar')];
		if (!$avatar)
			return response()->json(['success' => false, 'code' => 'avatar.avatar_mismatch']);

		\Auth::user()->update(['avatar' => $avatar['path']]);

		return response()->json(['success' => true]);
	}

	// $messages = [
	// 	'required' => 'required',
	// 	'unique' => 'unique',
	// 	'string' => 'string',
	// 	'email' => 'email',
	// 	'min' => 'min::min',
	// 	'max' => 'max::max'
	// ];

	public function updatePassword(Request $request)
	{
		$messages = ['required' => 'required', 'string' => 'string', 'email' => 'email', 'max' => 'max::max'];
		$validator = \Validator::make($request->all(), [
			'password' => 'required|string|min:6|max:30',
			'password-new' => 'required|string|min:6|max:30',
		], $messages);

		if ($validator->fails())
			return response()->json([
				'status' => 'error',
				'errors' => $validator->errors()->messages()
			]);

		$user = \Auth::user();
		if (!\Hash::check($request->password, $user->password))
			return response()->json([
				'success' => false,
				'code'    => 'auth.password_mismatch'
			]);

		$newPassword = $request->get('password-new');
		$update = $user->update(['password' => \Hash::make($newPassword)]);
		if (!$update)
			return response()->json([
				'success' => false,
				'code'    => 'auth.error_password_update'
			]);

		return response()->json(['success' => true]);
	}

	public function updateEmail(Request $request)
	{
		$messages = ['required' => 'required', 'string' => 'string', 'email' => 'email', 'max' => 'max::max'];
		$validator = \Validator::make($request->all(), [
			'email' => 'required|email',
		], $messages);

		if ($validator->fails())
			return response()->json([
				'status' => 'error',
				'errors' => $validator->errors()->messages()
			], 200);

		$user =	\Auth::user();
		$user->update(['email' => $request->get('email'), 'email_verified_at' => NULL]);

		\Mail::to($user->email)->send(new VerificationEmail($user));

		return response()->json(['success' => true]);
	}
}
