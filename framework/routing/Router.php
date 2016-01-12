<?php
namespace routing;

use InvalidArgumentException;
use routing\exceptions\RouteNotFoundException;

class Router
{
	/** @var Route[] */
	private static $routes = [];
	
	public static function addRoute(string $name, string $url, callable $handler)
	{
		// using name as key only to prevent multiple routes with the same name
		self::$routes[$name] = new Route($url, $handler, $name);
	}
	
	public static function render(string $path)
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
			if ($route->render($path))
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
		foreach (self::$routes as $route)
		{
			if ($route->getName() === $name)
			{
				return $route->generateLink($parameters);
			}
		}
		
		return null;
	}
}
