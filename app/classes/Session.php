<?php
namespace app\classes;

use js\tools\commons\traits\StaticDataWriter;
use simpleframe\Data;

class Session
{
	use StaticDataWriter;
	
	private static $users = [
		'admin' => 'admin',
	];
	
	protected static function load(): array
	{
		return $_SESSION;
	}
	
	public static function isLoggedIn()
	{
		return self::exists('user.login');
	}
	
	/**
	 * Authenticate a user based on submitted data.
	 *
	 * @param Data $requestData
	 * @return string|null the error message on failure or null if no error occurred
	 */
	public static function login(Data $requestData)
	{
		if (!$requestData->get('login') || !$requestData->get('password'))
		{
			return 'Missing login';
		}
		else if (!isset(self::$users[$requestData->get('login')]))
		{
			return 'Invalid login';
		}
		else if (self::$users[$requestData->get('login')] !== $requestData->get('password'))
		{
			return 'Invalid password';
		}
		
		self::set(
			'user',
			[
				'login' => $requestData->get('login'),
			]
		);
		
		return null;
	}
	
	public static function logout(): bool
	{
		if (self::isLoggedIn())
		{
			self::set('user', []);
			
			return true;
		}
		
		return false;
	}
}
