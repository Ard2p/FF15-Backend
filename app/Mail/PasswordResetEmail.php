<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\User;

class PasswordResetEmail extends Mailable implements ShouldQueue
{
	use Queueable, SerializesModels;

	protected $user;
	protected $new_password;

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public function __construct(User $user, $new_password)
	{
		$this->user = $user;
		$this->new_password = $new_password;
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build()
	{
		$this->theme = 'ff15';

		return $this->markdown('emails.auth.password-reset')->with(['new_password' => $this->new_password]);
	}
}
