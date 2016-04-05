<?php
namespace routing;

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
	 * @param array $namedParams : a map of parameter names => values
	 * @param array $getParams : GET parameters to append to the query
	 * @param bool $isAbsolute : if true, an absolute URL will be generated
	 * @return null|string the link to the named route with the parameters replaced, or null if no route was found
	 */
	public static function link(string $name, array $namedParams = [], array $getParams = [], bool $isAbsolute = false)
	{
		if (isset(self::$routes[$name]))
		{
			$route = self::$routes[$name]->generateLink($namedParams);
			
			if (empty($getParams))
			{
				$query = '';
			}
			else
			{
				$query = '?' . http_build_query($getParams, '', '&');
			}
			
			$prefix = '';
			
			if ($isAbsolute)
			{
				$prefix = 'http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '//' //
					. $_SERVER['HTTP_HOST'];
			}
			
			return $prefix . $route . $query;
		}
		
		return null;
	}
}
