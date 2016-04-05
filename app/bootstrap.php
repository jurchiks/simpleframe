<?php
// Put any custom startup actions here, like initializing database connections, registering shutdown handlers, etc.
// This ensures maximum flexibility as you can use whatever tools you want and are not bound by the framework.
use classes\ExampleConsoleHandler;
use classes\Session;
use responses\RedirectResponse;
use routing\Router;

EventHandler::on(EventHandler::ON_ROUTE_MATCH, function (string $name)
{
	if (substr($name, 0, 5) === 'admin')
	{
		if (!Session::isLoggedIn() && ($name !== 'admin.login'))
		{
			(new RedirectResponse(Router::link('admin.login')))->render();
		}
	}
});

App::registerConsoleHandler(ExampleConsoleHandler::class);

session_start();
