<?php
use js\tools\commons\exceptions\LogException;
use js\tools\commons\templating\Engine;
use responses\ErrorResponse;
use responses\ExceptionResponse;
use responses\TemplateResponse;
use routing\exceptions\RouteNotFoundException;
use routing\exceptions\RouteParameterException;
use routing\Router;

final class App
{
	const REQUEST_METHODS = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'];
	
	/** @var callable[] */
	private static $exceptionHandlers = [];
	/** @var callable[] */
	private static $shutdownHandlers = [];
	/** @var callable[] */
	private static $consoleHandlers = [];
	
	/**
	 * @param string $exceptionClass : the full name of the exception to handle, e.g.\name\space\Exception::class
	 * @param callable $handler : the handler to call if an uncaught exception of the specified class has been thrown;
	 * must return a boolean value - false if handling is to be passed to the framework, true if all work is done in
	 *     the handler
	 */
	public static function registerExceptionHandler(string $exceptionClass, callable $handler)
	{
		self::$exceptionHandlers[$exceptionClass] = $handler;
	}
	
	public static function registerShutdownHandler(callable $handler)
	{
		self::$shutdownHandlers[] = $handler;
	}
	
	public static function registerConsoleHandler(string $handler, string $command = null)
	{
		if ($command === null)
		{
			if (!class_exists($handler)
				|| !isset(class_parents($handler)[ConsoleHandler::class]))
			{
				throw new RuntimeException('Invalid console handler, only callables and classes allowed');
			}
			
			$handlers = $handler::listMethods(); // PHPStorm warns about this, but it is actually supported by PHP
			
			foreach ($handlers as $handler)
			{
				if (isset(self::$consoleHandlers[$handler[1]]))
				{
					throw new RuntimeException('Duplicate console handler for command ' . $handler[1]);
				}
				
				self::$consoleHandlers[$handler[1]] = $handler;
			}
		}
		else
		{
			if (!is_callable($handler))
			{
				throw new RuntimeException('Invalid console handler, only callables and classes allowed');
			}
			
			if (isset(self::$consoleHandlers[$command]))
			{
				throw new RuntimeException('Duplicate console handler for command ' . $command);
			}
			
			self::$consoleHandlers[$command] = $handler;
		}
	}
	
	public static function init()
	{
		set_exception_handler(
			function ($e)
			{
				if (isset(self::$exceptionHandlers[get_class($e)]))
				{
					$handler = self::$exceptionHandlers[get_class($e)];
					
					if ($handler($e))
					{
						return;
					}
				}
				
				if ($e instanceof RouteNotFoundException)
				{
					// exception for a special exception; if a user mistypes a URL, show a 404 not found page.
					(new ErrorResponse(new TemplateResponse('page_not_found', ['exception' => $e]), 404))->render();
					
					return;
				}
				
				if ($e instanceof RouteParameterException)
				{
					$data = ['exception' => $e];
					(new ErrorResponse(new TemplateResponse('route_invalid_parameters', $data), 404))->render();
					
					return;
				}
				
				if ($e instanceof UnexpectedValueException)
				{
					$trace = $e->getTrace();
					
					if (isset($trace[0]['class'])
						&& ($trace[0]['class'] === LogException::class)
						&& (stripos($e->getMessage(), 'check permissions') !== false)
					)
					{
						error_log('Cannot write to app log files, please check permissions for ' . LOG_DIR);
						(new ExceptionResponse($e))->render();
						
						return;
					}
				}
				
				Logger::log(Logger::CRITICAL, strval($e));
				(new ExceptionResponse($e))->render();
			}
		);
		
		set_error_handler(
			function ($errno, $errstr, $file, $line)
			{
				static $logLevels = [
					E_NOTICE            => Logger::NOTICE,
					E_USER_NOTICE       => Logger::NOTICE,
					E_STRICT            => Logger::NOTICE,
					E_DEPRECATED        => Logger::NOTICE,
					E_USER_DEPRECATED   => Logger::NOTICE,
					E_WARNING           => Logger::WARNING,
					E_USER_WARNING      => Logger::WARNING,
					E_ERROR             => Logger::ERROR,
					E_RECOVERABLE_ERROR => Logger::ERROR,
					E_USER_ERROR        => Logger::ERROR,
				];
				
				$message = 'Error in file ' . $file . ', line ' . $line . ':' . PHP_EOL //
					. $errstr . ' (code: ' . $errno . ')';
				
				Logger::log($logLevels[$errno] ?? Logger::ERROR, $message);
				
				(new ErrorResponse($message))->render();
				
				if (isset($logLevels[$errno]) && ($logLevels[$errno] === Logger::NOTICE))
				{
					// notice level errors usually don't break code, so we continue;
					// it has been logged and displayed already anyway
					return false;
				}
				
				return true;
			}
		);
		
		register_shutdown_function(
			function ()
			{
				/**
				 * @param Exception|Throwable $e
				 */
				$log = function ($e)
				{
					Logger::log(
						Logger::CRITICAL,
						sprintf(
							"%s thrown in shutdown handler: %s\n%s",
							get_class($e),
							$e->getMessage(),
							$e->getTraceAsString()
						)
					);
				};
				
				foreach (self::$shutdownHandlers as $handler)
				{
					try
					{
						$handler();
					}
					catch (Exception $e)
					{
						// TODO HHVM support for Throwable required
						$log($e);
					}
					catch (Throwable $t)
					{
						$log($t);
					}
				}
			}
		);
	}
	
	public static function getTemplatingEngine()
	{
		static $engine = null;
		
		if ($engine === null)
		{
			$engine = new Engine(ROOT_DIR . '/app/templates/');
			$engine->addRoot(ROOT_DIR . '/framework/templates/');
		}
		
		return $engine;
	}
	
	public static function render()
	{
		$argv = ($GLOBALS['argv'] ?? []);
		$data = [];
		
		if (isset($argv[0]))
		{
			if (!isset($argv[1], $argv[2]) || !in_array($argv[1], self::REQUEST_METHODS))
			{
				if (isset(self::$consoleHandlers[$argv[1]]))
				{
					$handler = self::$consoleHandlers[$argv[1]]; // PHPStorm and (self::$var)($args) again...
					$handler(...array_slice($argv, 2));
					exit();
				}
				
				echo 'Examples:', PHP_EOL, //
					"\tphp index.php method path[ data]", PHP_EOL, //
					"\tphp index.php get /foo", PHP_EOL, //
					"\tphp index.php get /foo/bar a=1&b=2", PHP_EOL, //
					"\tphp index.php post /foo/bar a=1&b=2", PHP_EOL, //
					"\tphp index.php delete /foo/bar a=1&b=2", PHP_EOL, //
					"\t OR", PHP_EOL, //
					"\tphp index.php command[ arguments]";
				exit(1);
			}
			
			$method = $argv[1];
			$route = $argv[2];
			$referer = '';
			
			if (isset($argv[3]))
			{
				parse_str($argv[3], $data);
			}
		}
		else
		{
			$method = strtolower($_SERVER['REQUEST_METHOD']);
			$route = $_SERVER['REQUEST_URI'];
			$referer = $_SERVER['HTTP_REFERER'] ?? '';
			
			if (!in_array($method, self::REQUEST_METHODS))
			{
				throw new RuntimeException('Unsupported request method ' . $method);
			}
			
			if ($method === 'get')
			{
				$data = $_GET;
			}
			else if ($method === 'post')
			{
				$data = $_POST;
			}
			else
			{
				// PHP does not automatically populate $_PUT and $_DELETE variables
				parse_str(file_get_contents('php://input'), $data);
			}
		}
		
		$request = new Request($method, $route, $data, $referer);
		
		Router::render($request);
	}
}
