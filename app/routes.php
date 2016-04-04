<?php
use classes\User;
use routing\Router;
use controllers\ExampleController;

// UrlParameter names in route URL must match handler parameter names.
// This is to be sure that we correctly check the types and cast the values of the parameters.
Router::addRoute(
	'index',
	'/{user}',
	function (User $user = null)
	{
		return 'Hello, ' . ($user ? $user->getName() : 'World') . '!';
	}
);
Router::addRoute(
	'index2',
	'/foo-{user}-bar',
	function (User $user = null) // it is awkward if you don't specify the parameter (/foo--bar), but it works
	{
		return 'Hello, ' . ($user ? $user->getName() : 'World') . '!';
	}
);
// route names have no strict scheme; anything (reasonable) will work
Router::addRoute('example.index', '/example', [ExampleController::class, 'index']);
Router::addRoute('example.put', '/example/put', [ExampleController::class, 'put'], ['put']);
Router::addRoute('example.test', '/example/test', [ExampleController::class, 'test']);
Router::addRoute('example.test2', '/example/test2/{user}/{id2}', [ExampleController::class, 'test2']);
