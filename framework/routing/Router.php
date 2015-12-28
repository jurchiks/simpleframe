<?php
namespace routing;

use InvalidArgumentException;
use routing\exceptions\RouteException;
use routing\exceptions\RouteNotFoundException;

class Router
{
	public static function render(string $path)
	{
		foreach (self::getRoutes() as $route)
		{
			if ($route->render($path))
			{
				return;
			}
		}
		
		throw new RouteNotFoundException('No route defined for URL ' . $path);
	}
	
	/**
	 * @param string $name : the name of the route to link
	 * @param array $parameters : a map of parameter names => values
	 * @return string|null the link to the named route with the parameters replaced, or null if no route was found
	 * @throws InvalidArgumentException if the parameters are invalid
	 */
	public static function link(string $name, array $parameters = [])
	{
		foreach (self::getRoutes() as $route)
		{
			if ($route->getName() === $name)
			{
				return $route->getUrl($parameters);
			}
		}
		
		return null;
	}
	
	/**
	 * @return Route[]
	 */
	private static function getRoutes()
	{
		static $routes = null;
		
		if (is_null($routes))
		{
			if (!file_exists(ROOT_DIR . '/app/routes.php'))
			{
				throw new RouteException('Missing ' . ROOT_DIR . '/app/routes.php');
			}
			
			$routes = require ROOT_DIR . '/app/routes.php';
			
			if (!is_array($routes))
			{
				throw new RouteException('Invalid route definitions');
			}
			
			foreach ($routes as $url => $handler)
			{
				if (!is_string($url))
				{
					throw new RouteException(sprintf('Invalid route URL %s', strval($url)));
				}
				
				if (is_array($handler) && isset($handler['name']))
				{
					if (!is_string($handler['name']))
					{
						throw new RouteException(
							sprintf(
								'Invalid route handler name - %s %s, must be string',
								gettype($handler['name']),
								strval($handler['name'])
							)
						);
					}
					
					$name = $handler['name'];
					
					unset($handler['name']);
				}
				else
				{
					$name = null;
				}
				
				if (!is_callable($handler, true))
				{
					throw new RouteException('Invalid route handler for ' . $url . ', must be callable');
				}
				
				$routes[$url] = new Route($url, $handler, $name);
			}
			
			usort($routes, function (Route $a, Route $b)
			{
				return (strlen($b->getUrl()) <=> strlen($a->getUrl()));
			});
		}
		
		return $routes;
	}
}
