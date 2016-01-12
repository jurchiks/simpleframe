<?php
require __DIR__ . '/../framework/autoloader.php';

if (isset($argv[0]) && ($argv[0] === basename(__FILE__)))
{
	if (isset($argv[1], $argv[2]) && ($argv[1] === 'route'))
	{
		$route = $argv[2];
	}
	else
	{
		echo 'Example: php index.php route /foo/bar', PHP_EOL;
		exit(1);
	}
	
	if (isset($argv[3], $argv[4]) && ($argv[3] === 'post'))
	{
		parse_str($argv[4], $_POST);
	}
}
else
{
	$route = $_SERVER['REQUEST_URI'];
}

App::render($route);
