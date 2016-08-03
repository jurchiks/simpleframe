<?php
// Put any custom startup actions here, like initializing database connections, registering shutdown handlers, etc.
// This ensures maximum flexibility as you can use whatever tools you want and are not bound by the framework.
use app\classes\ExampleConsoleHandler;
use app\classes\Session;
use js\tools\commons\http\Request;
use simpleframe\App;
use simpleframe\EventHandler;
use simpleframe\responses\RedirectResponse;
use simpleframe\routing\Route;
use simpleframe\routing\Router;

EventHandler::on(
	EventHandler::ON_ROUTE_MATCH,
	function (Route $route, array $parameters, Request $request)
	{
		if ($route->getRequireHttps() && !$request->isSecure())
		{
			$url = Router::link($route->getName(), $parameters)->setScheme('https');
			(new RedirectResponse($url))->render();
		}
		
		if (substr($route->getName(), 0, 5) === 'admin')
		{
			if (!Session::isLoggedIn() && ($route->getName() !== 'admin.login'))
			{
				(new RedirectResponse(Router::link('admin.login')))->render();
			}
		}
	}
);

App::registerConsoleHandler(ExampleConsoleHandler::class);

session_start();
