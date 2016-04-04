<?php
namespace routing;

use App;
use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Request;
use responses\Response;
use routing\exceptions\RouteException;
use routing\exceptions\RouteParameterException;
use RuntimeException;

class Route
{
	/** @var string */
	private $name;
	/** @var callable */
	private $handler;
	/** @var string */
	private $url;
	/** @var array[] */
	private $parameters;
	/** @var string */
	private $pattern;
	/** @var string[] */
	private $methods;
	
	private static $primitiveTypePatterns = [
		'int'    => '-?\d+',
		'float'  => '-?\d+(\.\d+)?',
		'bool'   => '0|1|false|true',
		'string' => '[^/]+',
	];
	
	private static $injectedParameters = [
		Request::class,
	];
	
	public function __construct(string $url, callable $handler, string $name, array $methods)
	{
		$this->name = $name;
		$this->handler = $handler;
		$this->url = '/' . trim($url, '/');
		$this->parameters = $this->parseParameters();
		$this->pattern = self::generatePattern($this->url, $this->parameters);
		
		if (empty($methods))
		{
			$this->methods = App::REQUEST_METHODS;
		}
		else
		{
			foreach ($methods as $method)
			{
				if (!in_array($method, App::REQUEST_METHODS))
				{
					throw new RouteException('Unsupported method "' . $method . '"');
				}
			}
			
			$this->methods = $methods;
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
	
	public function generateLink(array $userParameters = [])
	{
		$url = $this->url;
		
		foreach ($this->parameters as $name => $parameter)
		{
			if (isset($userParameters[$name]))
			{
				// parameter is provided; validate and replace
				if ($parameter['type'] !== self::getVariableType($userParameters[$name]))
				{
					throw new InvalidArgumentException(
						sprintf(
							'Route "%s" parameter "%s" does not match the required type "%s"',
							$this->name,
							$name,
							$parameter['type']
						)
					);
				}
				
				if (is_bool($userParameters[$name]))
				{
					// strval(false) === '', but we need 0; casting false to int yields 0
					$userParameters[$name] = intval($userParameters[$name]);
				}
				
				$url = str_replace('{' . $name . '}', strval($userParameters[$name]), $url);
			}
			else if ($parameter['optional'])
			{
				// parameter is not provided, but is optional; remove from URL
				$url = str_replace($parameter['search'], '', $url);
			}
			else
			{
				// parameter is not optional and not provided
				throw new InvalidArgumentException('Missing required route parameter "' . $name . '"');
			}
		}
		
		return $url;
	}
	
	public function render(Request $request)
	{
		if (!in_array($request->getMethod(), $this->methods))
		{
			return false;
		}
		
		if (preg_match($this->pattern, $request->getPath(), $urlParameters) !== 1)
		{
			return false;
		}
		
		$realParameters = self::getParameterValues($this->getHandlerParameters(), $urlParameters, $request);
		$handler = $this->handler; // because PHPStorm doesn't recognise PHP7's ($this->handler)($params)...
		$response = $handler(...$realParameters);
		
		self::handleResponse($response);
		
		return true;
	}
	
	/**
	 * @return ReflectionParameter[]
	 */
	private function getHandlerParameters(): array
	{
		if ($this->handler instanceof Closure)
		{
			$refHandler = new ReflectionFunction($this->handler);
		}
		else
		{
			$refHandler = new ReflectionMethod($this->handler[0], $this->handler[1]);
		}
		
		return $refHandler->getParameters();
	}
	
	private function parseParameters(): array
	{
		$parameters = [];
		
		foreach ($this->getHandlerParameters() as $refParameter)
		{
			$name = $refParameter->getName();
			$type = self::getParameterType($refParameter);
			$class = $refParameter->getClass();
			
			if (is_object($class))
			{
				if (in_array($type, self::$injectedParameters))
				{
					// skip injected parameters
					continue;
				}
				
				if (!$class->implementsInterface(Parameter::class))
				{
					throw new RouteParameterException(
						'Route parameter "' . $name . '" does not implement the Parameter interface'
						. ' and is not an injection'
					);
				}
			}
			else if (!isset(self::$primitiveTypePatterns[$type]))
			{
				throw new RouteParameterException(
					'Unsupported parameter type "' . $type . '" for parameter "' . $name . '"'
				);
			}
			
			$parameters[$name] = self::getParameterData($refParameter, $type);
		}
		
		return $parameters;
	}
	
	private static function getParameterData(ReflectionParameter $parameter, string $type): array
	{
		$class = $parameter->getClass();
		
		$data = [
			'type' => $type,
			'search' => '{' . $parameter->getName() . '}',
		];
		
		if (is_object($class)) // implements Parameter
		{
			$data['pattern'] = $class->getMethod('getPattern')->invoke(null);
			$data['optional'] = $parameter->isOptional() || $class->getMethod('isOptional')->invoke(null);
		}
		else
		{
			$data['pattern'] = self::$primitiveTypePatterns[$type];
			$data['optional'] = $parameter->isOptional();
		}
		
		return $data;
	}
	
	private static function generatePattern(string $url, array &$parameters): string
	{
		foreach ($parameters as $name => $parameter)
		{
			$search = $parameter['search'];
			$replacement = '(?P<' . $name . '>' . $parameter['pattern'] . ')';
			
			$start = strpos($url, $search);
			$end = $start + strlen($search);
			
			if ($parameter['optional'])
			{
				// if a parameter is optional and is enclosed in slashes, like so - "/{foo}/..." -,
				// then '/{foo}' as a whole can be optional
				// the only exception is if the whole URL is just "/{foo}", in which case the / cannot be included
				// in all other cases, only '{foo}' itself can be optional, although arguably it shouldn't be at all
				if (($url[$start - 1] === '/') // right before it is a slash
					&& ((($start > 1) && ($end === strlen($url))) // is the last, but not the first thing in the URL
						|| (($end > strlen($url)) && ($url[$end] === '/')))) // is not the last thing in the URL
				{
					$search = '/' . $search;
					$replacement = '(/' . $replacement . ')';
					
					// FIXME yeah, this is a side-effect of this method, but there's no other place to put this
					$parameters[$name]['search'] = $search;
				}
				
				$replacement .= '?';
			}
			
			$url = str_replace($search, $replacement, $url);
		}
		
		return '#\A' . $url . '\z#i';
	}
	
	private static function getParameterValues(array $handlerParameters, array $urlParameters, Request $request): array
	{
		// ensure the parameters are passed in in the same order they are declared in the method
		// also check for optional parameters and custom injections
		$values = [];
		
		/** @var ReflectionParameter[] $handlerParameters */
		foreach ($handlerParameters as $parameter)
		{
			$value = ($urlParameters[$parameter->getName()] ?? null);
			
			if (is_object($parameter->getClass()))
			{
				$values[] = self::getParameterInjection($parameter, $value ?: '', $request);
			}
			else
			{
				$values[] = self::getParameterValue($parameter, $value);
			}
		}
		
		return $values;
	}
	
	private static function getParameterInjection(ReflectionParameter $parameter, string $value, Request $request)
	{
		$class = $parameter->getClass();
		
		switch ($class->getName())
		{
			case Request::class:
				return $request;
			default:
				if ($class->implementsInterface(Parameter::class))
				{
					try
					{
						return $class->newInstance($value);
					}
					catch (Exception $e)
					{
						if ($parameter->isOptional())
						{
							return $parameter->getDefaultValue();
						}
						
						if ($class->getMethod('isOptional')->invoke(null))
						{
							return $class->getMethod('getDefault')->invoke(null);
						}
						
						throw $e;
					}
				}
		}
		
		throw new RouteParameterException('Unsupported parameter "' . $class->getName() . '"');
	}
	
	private static function getParameterValue(ReflectionParameter $parameter, string $value = null)
	{
		if ($value !== null)
		{
			return self::tryCastParameter($parameter, $value);
		}
		
		if ($parameter->isOptional())
		{
			return $parameter->getDefaultValue();
		}
		
		throw new RouteParameterException(
			'Route missing required parameter "' . $parameter->getName() . '"' //
			. ($parameter->hasType() ? ' (' . self::getParameterType($parameter) . ')' : '')
		);
	}
	
	private static function tryCastParameter(ReflectionParameter $parameter, string $value)
	{
		switch (self::getParameterType($parameter))
		{
			case 'int':
				return intval($value);
			case 'float':
				return floatval($value);
			case 'bool':
				if (($value === '1') || (strcasecmp($value, 'true') === 0))
				{
					return true;
				}
				
				return false;
			case 'string':
			default:
				return $value;
		}
	}
	
	private static function getParameterType(ReflectionParameter $parameter)
	{
		// TODO HHVM support for PHP7 scalars required
		static $map = [
			'HH\int'    => 'int',
			'HH\float'  => 'float',
			'HH\bool'   => 'bool',
			'HH\string' => 'string',
		];
		
		if (!$parameter->hasType())
		{
			// no explicit type specified, assuming anything is allowed
			return 'string';
		}
		
		$type = $parameter->getType()->__toString();
		
		return ($map[$type] ?? $type);
	}
	
	private static function getVariableType($variable)
	{
		static $map = [
			'integer' => 'int',
			'boolean' => 'bool',
			'double'  => 'float',
		];
		
		$type = gettype($variable);
		
		if ($type === 'object')
		{
			$type = get_class($variable);
		}
		
		return ($map[$type] ?? $type);
	}
	
	private static function handleResponse($response)
	{
		if (is_null($response))
		{
			echo 'NULL';
		}
		else if (is_bool($response))
		{
			echo $response ? 'TRUE' : 'FALSE';
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
				sprintf(
					'Unsupported response type "%s", consider using one of the Response classes',
					gettype($response)
				)
			);
		}
	}
}
