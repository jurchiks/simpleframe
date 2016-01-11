<?php
namespace routing;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use responses\Response;
use routing\exceptions\RouteException;
use routing\exceptions\RouteParameterException;
use RuntimeException;

class Route
{
	/** @var string */
	private $url;
	/** @var callable */
	private $handler;
	/** @var string */
	private $name;
	/** @var array[] */
	private $parameters = [];
	/** @var string */
	private $pattern = '';
	
	public function __construct(string $url, callable $handler, string $name = null)
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
	
	public function getUrl()
	{
		return $this->url;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function generateLink(array $parameters = [])
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
		else if (preg_match($this->pattern, $path, $parameters) !== 1)
		{
			return false;
		}
		
		$response = self::invokeAction($parameters, $this->handler);
		
		self::handleResponse($response);
		
		return true;
	}
	
	private static function invokeAction(array $urlParameters, callable $handler)
	{
		if ($handler instanceof Closure)
		{
			$reflectionClosure = new ReflectionFunction($handler);
			$realParameters = self::parseRealParameters($reflectionClosure->getParameters(), $urlParameters);
			
			return $reflectionClosure->invokeArgs($realParameters);
		}
		else
		{
			$reflectionMethod = new ReflectionMethod($handler[0], $handler[1]);
			$realParameters = self::parseRealParameters($reflectionMethod->getParameters(), $urlParameters);
			
			return $reflectionMethod->invokeArgs(null, $realParameters);
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
	
	private static function generatePattern(array $parameters)
	{
		$pattern = '~\A';
		
		foreach ($parameters as $parameter)
		{
			$pattern .= $parameter['prefix'];
			$pattern .= '(/(?P<' . $parameter['name'] . '>[^/]*))?';
		}
		
		$pattern .= '\z~i';
		
		return $pattern;
	}
	
	private static function parseRealParameters(array $handlerParameters, array $urlParameters)
	{
		// ensure the parameters are passed in in the same order they are declared in the method
		// also check for optional parameters and custom injections
		$realParameters = [];
		
		/** @var ReflectionParameter[] $handlerParameters */
		foreach ($handlerParameters as $parameter)
		{
			if (isset($urlParameters[$parameter->getName()]))
			{
				$realParameters[$parameter->getName()] = self::tryCastParameter(
					$parameter,
					$urlParameters[$parameter->getName()]
				);
			}
			else if ($parameter->isOptional())
			{
				$realParameters[$parameter->getName()] = $parameter->getDefaultValue();
			}
			else
			{
				throw new RouteParameterException(
					'Route missing required parameter ' . $parameter->getName() //
					. ($parameter->getType() ? ' (' . $parameter->getType()->__toString() . ')' : '')
				);
			}
		}
		
		return $realParameters;
	}
	
	private static function tryCastParameter(ReflectionParameter $parameter, string $value)
	{
		if (is_null($parameter->getType()))
		{
			// no explicit type specified, assuming anything is allowed
			$type = 'mixed';
		}
		else
		{
			$type = $parameter->getType()->__toString();
		}
		
		switch ($type)
		{
			case 'mixed':
			case 'string':
				return $value;
			case 'int':
				if (is_numeric($value))
				{
					return intval($value);
				}
				break;
			case 'float':
				if (is_numeric($value))
				{
					return floatval($value);
				}
				break;
			case 'bool':
				if (($value === '0') || (strcasecmp($value, 'false') === 0))
				{
					return false;
				}
				
				if (($value === '1') || (strcasecmp($value, 'true') === 0))
				{
					return true;
				}
				break;
			// TODO DI? custom parameters?
			default:
				throw new RouteException(sprintf('Unsupported parameter type %s', $type));
		}
		
		throw new RouteParameterException(
			sprintf('Route parameter %s has invalid value, must be %s', $parameter->getName(), $type)
		);
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
