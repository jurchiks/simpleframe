<?php

class Config
{
	/**
	 * @param string $name : the name of the config property to retrieve. Can be dot-separated for access to nested
	 *     properties, e.g. "database.host" will retrieve $config["database"]["host"] if it exists
	 * @param mixed $default : the default value to return if property was not found
	 * @return mixed whatever the config value or default value is
	 */
	public static function get(string $name, $default = null)
	{
		$config = self::load();
		
		if (isset($config[$name]))
		{
			return $config[$name];
		}
		
		if (strpos($name, '.') === false)
		{
			return $default;
		}
		
		$parts = explode('.', $name);
		$value = null;
		
		foreach ($parts as $key)
		{
			if (is_null($value))
			{
				// first level
				if (isset($config[$key]))
				{
					$value = $config[$key];
				}
			}
			else if (isset($value[$key]))
			{
				// nested levels, e.g. $name = "database.host"
				$value = $value[$key];
			}
		}
		
		return (is_null($value) ? $default : $value);
	}
	
	public static function getInt(string $name, int $default = 0): int
	{
		$value = self::get($name);
		
		return (is_int($value) ? $value : $default);
	}
	
	public static function getString(string $name, string $default = ''): string
	{
		$value = self::get($name);
		
		return (is_string($value) ? $value : $default);
	}
	
	public static function getBool(string $name, bool $default = false): bool
	{
		$value = self::get($name);
		
		return (is_bool($value) ? $value : $default);
	}
	
	public static function getArray(string $name, array $default = []): array
	{
		$value = self::get($name);
		
		return (is_array($value) ? $value : $default);
	}
	
	private static function load()
	{
		static $config = null;
		
		if (is_null($config))
		{
			if (file_exists(ROOT_DIR . '/app/config.global.php'))
			{
				$globalConfig = require ROOT_DIR . '/app/config.global.php';
				
				if (!is_array($globalConfig))
				{
					throw new RuntimeException('Invalid global config definition, must be an array');
				}
			}
			else
			{
				$globalConfig = [];
			}
			
			if (file_exists(ROOT_DIR . '/app/config.user.php'))
			{
				$userConfig = require ROOT_DIR . '/app/config.user.php';
				
				if (!is_array($userConfig))
				{
					throw new RuntimeException('Invalid user config definition, must be an array');
				}
			}
			else
			{
				$userConfig = [];
			}
			
			$config = array_merge($globalConfig, $userConfig);
		}
		
		return $config;
	}
}
