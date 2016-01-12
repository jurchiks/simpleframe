<?php
use routing\Router;
use controllers\ExampleController;

// Parameter names in route URL must match handler parameter names.
// This is to be sure that we correctly check the types and cast the values of the parameters.
Router::addRoute(
	'index',
	'/:name',
	function (string $name = null)
	{
		return 'Hello, ' . ($name ?: 'World') . '!';
	}
);
// route names have no strict scheme; anything (reasonable) will work
Router::addRoute('example.index', '/example', [ExampleController::class, 'index']);
Router::addRoute('example.test', '/example/test', [ExampleController::class, 'test']);
Router::addRoute('example.test2', '/example/test2/:id/:id2', [ExampleController::class, 'test2']);
