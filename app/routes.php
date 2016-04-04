<?php
use classes\Language;
use classes\User;
use controllers\ExampleController;
use routing\Router;

// UrlParameter names in route URL must match handler parameter names.
// This is to be sure that we correctly check the types and cast the values of the parameters.
Router::addRoute(
	'index',
	'/{lang}/{user}',
	function (Language $lang, User $user = null)
	{
		return 'Hello, ' . ($user ? $user->getName() : 'World') . '! Your locale is ' . $lang->getValue();
	}
);
Router::addRoute(
	'index2',
	'/{lang}/foo-{user}-bar',
	function (Language $lang, User $user = null)
	{
		// it is awkward if you don't specify the parameter (/foo--bar), but it works
		return 'Hello, ' . ($user ? $user->getName() : 'World') . '! Your locale is ' . $lang->getValue();
	}
);
// route names have no strict scheme; anything (reasonable) will work
Router::addRoute('example.index', '/example', [ExampleController::class, 'index']);
Router::addRoute('example.put', '/example/put', [ExampleController::class, 'put'], ['put']);
Router::addRoute('example.test', '/example/test', [ExampleController::class, 'test']);
Router::addRoute('example.test2', '/example/test2/{user}/{id2}', [ExampleController::class, 'test2']);
