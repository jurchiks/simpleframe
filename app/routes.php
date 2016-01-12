<?php
use controllers\ExampleController;

return [
	// !!!Note!!! Parameter names in route URL must match handler parameter names!
	'/:name'                  => function (string $name = null)
	{
		return 'Hello, ' . ($name ?: 'World') . '!';
	},
	'/example'                => [ExampleController::class, 'index'],
	'/example/test'           => [ExampleController::class, 'test', 'name' => 'test'],
	'/example/test2/:id/:id2' => [ExampleController::class, 'test2', 'name' => 'test2'],
];
