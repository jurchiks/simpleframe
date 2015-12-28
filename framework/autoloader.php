<?php
define('ROOT_DIR', realpath(__DIR__ . '/../'));
define('LOG_DIR', ROOT_DIR . '/logs');

spl_autoload_register(
	function ($name)
	{
		$ds = '/';
		$name = str_replace('\\', $ds, $name);
		$name = ltrim($name, $ds);
		$file = $name . '.php';
		
		if (file_exists(ROOT_DIR . $ds . 'framework' . $ds . $file))
		{
			require ROOT_DIR . $ds . 'framework' . $ds . $file;
		}
		else if (file_exists(ROOT_DIR . $ds . 'app' . $ds . $file))
		{
			require ROOT_DIR . $ds . 'app' . $ds . $file;
		}
	}
);

App::init();

if (file_exists(ROOT_DIR . '/vendor/autoload.php'))
{
	require ROOT_DIR . '/vendor/autoload.php';
}

// user 
if (file_exists(ROOT_DIR . '/app/bootstrap.php'))
{
	require ROOT_DIR . '/app/bootstrap.php';
}

$timezone = Config::get('timezone');

if (!empty($timezone))
{
	date_default_timezone_set($timezone);
}
