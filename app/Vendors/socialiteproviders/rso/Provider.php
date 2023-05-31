<?php

namespace App\Vendors\socialiteproviders\rso;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
	/**
	 * Unique Provider Identifier.
	 */
	public const IDENTIFIER = 'RSO';

	/**
	 * {@inheritdoc}
	 */
	protected $scopes = ['openid' /*, 'cpid'*/];

	/**
	 * {@inheritdoc}
	 */
	protected function getAuthUrl($state)
	{
		return $this->buildAuthUrlFromBase(
			'https://auth.riotgames.com/authorize',
			$state
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTokenUrl()
	{
		return 'https://auth.riotgames.com/token';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getUserByToken($token)
	{
		$response = $this->getHttpClient()->get(
			'https://ru.api.riotgames.com/lol/summoner/v4/summoners/me',
			['headers' => ['Authorization' => 'Bearer ' . $token]]
		);

		return json_decode($response->getBody()->getContents(), true);
	}


	/**
	 * Get the access token response for the given code.
	 *
	 * @param  string  $code
	 * @return array
	 */
	public function getAccessTokenResponse($code)
	{
		$response = $this->getHttpClient()->post($this->getTokenUrl(), [
			'auth' => [$this->clientId, $this->clientSecret],
			'headers' => ['Accept' => 'application/json'],
			'form_params' => $this->getTokenFields($code),
		]);
		return json_decode($response->getBody(), true);
	}


	/**
	 * @param array $user
	 *
	 * @return string|null
	 *
	 * @see https://discord.com/developers/docs/reference#image-formatting-cdn-endpoints
	 */
	protected function formatAvatar(array $user)
	{
		if (empty($user['avatar'])) {
			return null;
		}

		$isGif = preg_match('/a_.+/m', $user['avatar']) === 1;
		$extension = $this->getConfig('allow_gif_avatars', true) && $isGif ? 'gif' :
			$this->getConfig('avatar_default_extension', 'jpg');

		return sprintf('https://cdn.discordapp.com/avatars/%s/%s.%s', $user['id'], $user['avatar'], $extension);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function mapUserToObject(array $user)
	{
		dd($user);
		// return (new User())->setRaw($user)->map([
		// 	'id'       => $user['id'],
		// 	'nickname' => sprintf('%s#%s', $user['username'], $user['discriminator']),
		// 	'name'     => $user['username'],
		// 	'email'    => (array_key_exists('email', $user)) ? $user['email'] : null,
		// 	// 'avatar'   => $this->formatAvatar($user),
		// ]);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTokenFields($code)
	{
		return array_merge(parent::getTokenFields($code), [
			'grant_type' => 'authorization_code'
		]);
	}
}
