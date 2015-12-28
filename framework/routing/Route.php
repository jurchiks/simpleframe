<?php
namespace routing;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use responses\Response;
use routing\exceptions\RouteException;
use RuntimeException;

class Route
{
	/** @var string */
	private $url;
	/** @var array */
	private $handler;
	/** @var string */
	private $name;
	/** @var array[] */
	private $parameters = [];
	/** @var string */
	private $pattern = '';
	
	public function __construct(string $url, $handler, string $name = null)
	{
		$this->url = $url;
		$this->handler = $handler;
		$this->name = $name;
		
		if (strpos($url, ':') !== false)
		{
			$this->parameters = self::parseRouteParameters($url);
			$this->pattern = self::generatePattern($this->parameters);
		}
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getUrl(array $parameters = [])
	{
		$url = $this->url;
		
		foreach ($this->parameters as $parameter)
		{
			if (isset($parameters[$parameter['name']]))
			{
				// TODO validate parameter type
				// parameter is provided; validate and replace
				if ($parameters[$parameter['name']] === false)
				{
					throw new InvalidArgumentException(
						'Route parameter "' . $parameter['name'] . '" does not match the required type'
					);
				}
				
				$url = str_replace($parameter['full'], strval($parameters[$parameter['name']]), $url);
			}
			else if ($parameter['optional'])
			{
				// parameter is not provided, but is optional; remove from URL
				$url = str_replace('/' . $parameter['full'], '', $url);
			}
			else
			{
				// parameter is not optional and not provided
				throw new InvalidArgumentException('Missing required route parameter "' . $parameter['name'] . '"');
			}
		}
		
		return $url;
	}
	
	public function render(string $path)
	{
		if (empty($this->parameters))
		{
			if ($this->url !== $path)
			{
				return false;
			}
			
			$parameters = [];
		}
		else if (preg_match($this->pattern, $path, $parameters) === false)
		{
			return false;
		}
		
		$response = $this->parseAction($path, $parameters);
		
		self::handleResponse($response);
		return true;
	}
	
	private function parseAction(string $path, array $urlParameters)
	{
		if ($this->handler instanceof Closure)
		{
			$reflectionClosure = new ReflectionFunction($this->handler);
			$realParameters = self::parseRealParameters($reflectionClosure->getParameters(), $urlParameters);
			
			return $reflectionClosure->invokeArgs($realParameters);
		}
		else if (class_exists($this->handler[0]))
		{
			$controller = new $this->handler[0]();
			$reflectionClass = new ReflectionClass($controller);
			
			if (!$reflectionClass->hasMethod($this->handler[1]))
			{
				throw new RouteException(
					'No such method . ' . $this->handler[1] . ' in controller ' . $this->handler[0] . ' for route '
					. $path
				);
			}
			
			$method = $reflectionClass->getMethod($this->handler[1]);
			$realParameters = self::parseRealParameters($method->getParameters(), $urlParameters);
			
			return $method->invokeArgs($controller, $realParameters);
		}
		else
		{
			throw new RouteException('Invalid route handler for ' . $this->url);
		}
	}
	
	private static function parseRouteParameters(string $url)
	{
		$parameters = [];
		$offset = 0;
		
		while (($start = strpos($url, ':', $offset)) !== false)
		{
			$end = strpos($url, '/', $start);
			
			if ($end === false)
			{
				$length = strlen($url) - $start;
			}
			else
			{
				$length = $end - $start - 1;
			}
			
			$name = substr($url, $start + 1, $length);
			$full = ':' . $name;
			$isOptional = false;
			
			if (substr($name, -1) === '?')
			{
				$isOptional = true;
				$name = substr($name, 0, -1);
			}
			
			$parameters[] = [
				'prefix'   => substr($url, $offset, $start - $offset - 1),
				'name'     => $name,
				'optional' => $isOptional,
				'full'     => $full,
			];
			
			if ($end === false)
			{
				$offset = strlen($url);
			}
			else
			{
				$offset = $end;
			}
		}
		
		return $parameters;
	}
	
	private static function generatePattern($parameters)
	{
		$pattern = '~\A';
		
		foreach ($parameters as $parameter)
		{
			$pattern .= $parameter['prefix'];
			$pattern .= '(/(?P<' . $parameter['name'] . '>[^/]+))' //
				. ($parameter['optional'] ? '?' : '');
		}
		
		$pattern .= '\z~i';
		
		return $pattern;
	}
	
	/**
	 * @param ReflectionParameter[] $handlerParameters
	 * @param string[] $urlParameters
	 * @return array
	 */
	private static function parseRealParameters(array $handlerParameters, array $urlParameters)
	{
		$realParameters = [];
		
		// ensure the parameters are passed in in the same order they are declared in the method
		// also check for optional parameters and custom injections
		foreach ($handlerParameters as $parameter)
		{
			if (isset($urlParameters[$parameter->getName()]))
			{
				$value = $urlParameters[$parameter->getName()];
				
				if (is_int($value) || is_float($value))
				{
					$value = strval($value);
				}
				else if (is_bool($value))
				{
					$value = ($value ? '1' : '0');
				}
				else
				{
					// TODO add injections by type
					throw new InvalidArgumentException('Unsupported parameter type ' . gettype($value));
				}
				
				$realParameters[$parameter->getName()] = $urlParameters[$parameter->getName()];
			}
			else if ($parameter->isOptional())
			{
				$realParameters[$parameter->getName()] = $parameter->getDefaultValue();
			}
			else
			{
			}
		}
		
		return $realParameters;
	}
	
	private static function handleResponse($response)
	{
		if (is_null($response))
		{
			echo 'NULL';
		}
		else if (is_bool($response))
		{
			echo($response ? 'TRUE' : 'FALSE');
		}
		else if (is_scalar($response))
		{
			echo $response;
		}
		else if ($response instanceof Response)
		{
			$response->render();
		}
		else
		{
			throw new RuntimeException(
				sprintf('Unsupported response type %s, consider using a Response', gettype($response))
			);
		}
	}
}
