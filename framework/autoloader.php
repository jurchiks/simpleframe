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

// user initializers, event bindings, etc
if (file_exists(ROOT_DIR . '/app/bootstrap.php'))
{
	require ROOT_DIR . '/app/bootstrap.php';
}

// routes
if (file_exists(ROOT_DIR . '/app/routes.php'))
{
	require ROOT_DIR . '/app/routes.php';
}
else
{
	throw new RuntimeException('No routes defined, what do you expect to see here?');
}

$timezone = Config::get('timezone');

if (!empty($timezone))
{
	date_default_timezone_set($timezone);
}
