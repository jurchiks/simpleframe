<?php
use controllers\ExampleController;

return [
	// Optional parameters must be marked as such with ? after their names.
	// This is to avoid checking every route handler for optional parameters on page load.
	// !!!Note!!! Parameter names in route URL must match handler parameter names!
	'/:name?'                  => function (string $name = null)
	{
		return 'Hello, ' . ($name ?: 'World') . '!';
	},
	'/example'                 => [ExampleController::class, 'index'],
	'/example/test'            => [ExampleController::class, 'test', 'name' => 'test'],
	'/example/test2/:id/:id2?' => [ExampleController::class, 'test2', 'name' => 'test2'],
];
