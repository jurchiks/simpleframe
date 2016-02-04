<?php
namespace routing;

use InvalidArgumentException;
use Request;
use routing\exceptions\RouteNotFoundException;

class Router
{
	/** @var Route[] */
	private static $routes = [];
	
	public static function addRoute(string $name, string $url, callable $handler, array $methods = [])
	{
		// using name as key only to prevent multiple routes with the same name
		self::$routes[$name] = new Route($url, $handler, $name, $methods);
	}
	
	public static function render(Request $request)
	{
		// sort routes only when rendering instead of on every route addition
		uasort(
			self::$routes,
			function (Route $a, Route $b)
			{
				// sort by route length descending
				return (strlen($b->getUrl()) <=> strlen($a->getUrl()));
			}
		);
		
		foreach (self::$routes as $route)
		{
			if ($route->render($request))
			{
				return;
			}
		}
		
		throw new RouteNotFoundException();
	}
	
	/**
	 * @param string $name : the name of the route to link
	 * @param array $parameters : a map of parameter names => values
	 * @return string|null the link to the named route with the parameters replaced, or null if no route was found
	 * @throws InvalidArgumentException if the parameters are invalid
	 */
	public static function link(string $name, array $parameters = [])
	{
		if (isset(self::$routes[$name]))
		{
			return self::$routes[$name]->generateLink($parameters);
		}
		
		return null;
	}
}
