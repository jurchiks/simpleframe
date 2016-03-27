<?php
use js\tools\commons\traits\StaticDataAccessor;

class Config
{
	use StaticDataAccessor;
	
	protected static function getData(): array
	{
		static $config = null;
		
		if (is_null($config))
		{
			$globalConfig = self::loadFile(ROOT_DIR . '/app/config.global.php', true);
			$userConfig = self::loadFile(ROOT_DIR . '/app/config.user.php', false);
			$config = array_merge($globalConfig, $userConfig);
		}
		
		return $config;
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
}
