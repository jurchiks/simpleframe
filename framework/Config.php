<?php
use js\tools\commons\traits\StaticDataAccessor;

class Config
{
	use StaticDataAccessor;
	
	protected static function load(): array
	{
		$globalConfig = self::loadFile(ROOT_DIR . '/app/config.global.php', true);
		$userConfig = self::loadFile(ROOT_DIR . '/app/config.user.php', false);
		
		return self::mergeConfigs($globalConfig, $userConfig);
	}
	
	private static function loadFile(string $path, bool $isGlobal): array
	{
		if (file_exists($path))
		{
			$config = require $path;
			
			if (!is_array($config))
			{
				throw new RuntimeException('Invalid ' . ($isGlobal ? 'global' : 'user') . ' config definition, must be an array');
			}
		}
		else
		{
			$config = [];
		}
		
		return $config;
	}
	
	// This method differs from array_replace_recursive() in that if an array with only numeric keys exists
	// in both arrays, it will be completely replaced, not merged (i.e. only maps are merged, lists are replaced)
	private static function mergeConfigs(array $global, array $user)
	{
		foreach ($user as $key => $value)
		{
			if (!isset($global[$key])
				|| (gettype($value) !== gettype($global[$key]))
				|| !is_array($value))
			{
				$global[$key] = $value;
			}
			else if (self::isList($value) && self::isList($global[$key]))
			{
				$global[$key] = $value;
			}
			else
			{
				$global[$key] = self::mergeConfigs($global[$key], $value);
			}
		}
		
		return $global;
	}
	
	private static function isList(array $arr)
	{
		foreach ($arr as $key => $_)
		{
			if (!is_int($key))
			{
				return false;
			}
		}
		
		return true;
	}
}
