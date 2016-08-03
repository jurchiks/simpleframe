<?php
use app\classes\Language;
use app\classes\User;
use app\controllers\AdminController;
use app\controllers\ExampleController;
use js\tools\commons\http\Request;
use simpleframe\routing\Route;
use simpleframe\routing\Router;

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
Router::addRoutes(
	(new Route('index3', '/foo/{bar}', function (string $bar, Request $request)
	{
		return $bar . '|' . $request->getUri();
	}))
	->setRequireHttps(true)
);
// route names have no strict scheme; anything (reasonable) will work
Router::addRoute('example.index', '/example', [ExampleController::class, 'index']);
Router::addRoute('example.put', '/example/put', [ExampleController::class, 'put'], ['put']);
Router::addRoute('example.test', '/example/test', [ExampleController::class, 'test']);
Router::addRoute('example.test2', '/example/test2/{user}/{id2}', [ExampleController::class, 'test2']);

Router::addRoute('admin.index', '/admin', [AdminController::class, 'index']);
Router::addRoute('admin.login', '/admin/login', [AdminController::class, 'login']);
Router::addRoute('admin.logout', '/admin/logout', [AdminController::class, 'logout']);
