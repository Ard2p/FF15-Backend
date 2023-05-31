<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\User;

class VerificationEmail extends Mailable implements ShouldQueue
{
	use Queueable, SerializesModels;

	protected $user;

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public function __construct(User $user)
	{
		$this->user = $user;
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build()
	{
		$this->theme = 'ff15';

		$url = config('app.front_url') . 'verify';
		$url .= '?token=' .sha1(config('app.key') . '|' . $this->user->getEmailForVerification());
		$url .= '&email=' . $this->user->email;
		return $this->markdown('emails.auth.verify')->with(['url' => $url]);
	}
}
