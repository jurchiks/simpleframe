<?php
namespace routing;

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
	private $url;
	/** @var callable */
	private $handler;
	/** @var string */
	private $name;
	/** @var string[] */
	private $methods;
	/** @var array[] */
	private $parameters = [];
	/** @var string */
	private $pattern = '';
	
	private static $injectedParameters = [
		Request::class,
	];
	private static $allMethods = ['get', 'post', 'put', 'delete', 'head', 'options'];
	
	public function __construct(string $url, callable $handler, string $name, array $methods)
	{
		$this->url = $url;
		$this->handler = $handler;
		$this->name = $name;
		
		if (empty($methods))
		{
			$this->methods = self::$allMethods;
		}
		else
		{
			foreach ($methods as $method)
			{
				if (!in_array($method, self::$allMethods))
				{
					throw new RouteException('Unsupported method "' . $method . '"');
				}
			}
			
			$this->methods = $methods;
		}
		
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
	
	public function generateLink(array $userParameters = [])
	{
		$url = $this->url;
		
		foreach ($this->getFullParameters() as $name => $parameter)
		{
			$pattern = '~\/:' . $name . '\b~';
			
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
				
				$url = preg_replace($pattern, '/' . strval($userParameters[$name]), $url, 1);
			}
			else if ($parameter['optional'])
			{
				// parameter is not provided, but is optional; remove from URL
				$url = preg_replace($pattern, '', $url, 1);
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
		
		if (empty($this->parameters))
		{
			if ($this->url !== $request->getPath())
			{
				return false;
			}
			
			$parameters = [];
		}
		else if (preg_match($this->pattern, $request->getPath(), $parameters) !== 1)
		{
			return false;
		}
		
		$response = self::invokeAction($this->getReflectedHandler(), $parameters, $request);
		
		self::handleResponse($response);
		
		return true;
	}
	
	/**
	 * @return ReflectionFunction|ReflectionMethod
	 */
	private function getReflectedHandler()
	{
		static $refHandlers = [];
		
		if (empty($refHandlers[$this->url]))
		{
			if ($this->handler instanceof Closure)
			{
				$refHandler = new ReflectionFunction($this->handler);
			}
			else
			{
				$refHandler = new ReflectionMethod($this->handler[0], $this->handler[1]);
			}
			
			$refHandlers[$this->url] = $refHandler;
		}
		
		return $refHandlers[$this->url];
	}
	
	private function getFullParameters()
	{
		static $processed = [];
		
		if (empty($processed[$this->url]))
		{
			$refParameters = $this->getReflectedHandler()->getParameters();
			
			foreach ($refParameters as $refParameter)
			{
				if (is_object($refParameter->getClass()))
				{
					if (in_array($refParameter->getClass()->getName(), self::$injectedParameters))
					{
						// skip injected parameters
						continue;
					}
				}
				
				$name = $refParameter->getName();
				
				if (!isset($this->parameters[$name]))
				{
					throw new RouteException('Route definition missing handler parameter "' . $name . '"');
				}
				
				$this->parameters[$name]['optional'] = $refParameter->isOptional();
				$this->parameters[$name]['type'] = self::getParameterType($refParameter);
			}
			
			$processed[$this->url] = true;
		}
		
		return $this->parameters;
	}
	
	/**
	 * @param ReflectionFunction|ReflectionMethod $refHandler
	 * @param array $urlParameters
	 * @param Request $request
	 * @return mixed
	 */
	private static function invokeAction($refHandler, array $urlParameters, Request $request)
	{
		$realParameters = self::parseRealParameters($refHandler->getParameters(), $urlParameters, $request);
		
		if ($refHandler instanceof ReflectionFunction)
		{
			return $refHandler->invokeArgs($realParameters);
		}
		else
		{
			return $refHandler->invokeArgs(null, $realParameters);
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
			
			$parameters[$name] = [
				'prefix' => substr($url, $offset, $start - $offset - 1),
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
		
		foreach ($parameters as $name => $parameter)
		{
			$pattern .= $parameter['prefix'];
			$pattern .= '(/(?P<' . $name . '>[^/]*))?';
		}
		
		$pattern .= '\z~i';
		
		return $pattern;
	}
	
	private static function parseRealParameters(array $handlerParameters, array $urlParameters, Request $request)
	{
		// ensure the parameters are passed in in the same order they are declared in the method
		// also check for optional parameters and custom injections
		$realParameters = [];
		
		/** @var ReflectionParameter[] $handlerParameters */
		foreach ($handlerParameters as $parameter)
		{
			$value = ($urlParameters[$parameter->getName()] ?? null);
			
			if (is_object($parameter->getClass()))
			{
				$realParameters[$parameter->getName()] = self::parseParameterInjection($parameter, $value ?: '', $request);
			}
			else
			{
				$realParameters[$parameter->getName()] = self::parseParameterValue($parameter, $value);
			}
		}
		
		return $realParameters;
	}
	
	private static function parseParameterInjection(ReflectionParameter $parameter, string $value, Request $request)
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
						
						throw $e;
					}
				}
		}
		
		throw new RouteParameterException('Unsupported parameter "' . $class->getName() . '"');
	}
	
	private static function parseParameterValue(ReflectionParameter $parameter, string $value = null)
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
		$type = self::getParameterType($parameter);
		
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
			default:
				throw new RouteException(sprintf('Unsupported parameter type %s', $type));
		}
		
		throw new RouteParameterException(
			sprintf('Route parameter "%s" has an invalid value, must be %s', $parameter->getName(), $type)
		);
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
			return 'mixed';
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
