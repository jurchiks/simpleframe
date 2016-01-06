<?php
use responses\ErrorResponse;
use responses\ExceptionResponse;
use responses\TemplateResponse;
use routing\exceptions\RouteNotFoundException;
use routing\Router;

final class App
{
	/** @var callable[] */
	private static $exceptionHandlers = [];
	private static $shutdownHandlers = [];
	
	/**
	 * @param string $exceptionClass : the full name of the exception to handle, e.g.\name\space\Exception::class
	 * @param callable $handler : the handler to call if an uncaught exception of the specified class has been thrown;
	 * must return a boolean value - false if handling is to be passed to the framework, true if all work is done in the handler
	 */
	public static function registerExceptionHandler(string $exceptionClass, callable $handler)
	{
		self::$exceptionHandlers[$exceptionClass] = $handler;
	}
	
	public static function registerShutdownHandler(callable $handler)
	{
		self::$shutdownHandlers[] = $handler;
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
					(new ErrorResponse(new TemplateResponse('page_not_found'), 404))->render();
					
					return;
				}
				
				if ($e instanceof UnexpectedValueException)
				{
					$trace = $e->getTrace();
					
					if (isset($trace[0]['class'])
						&& ($trace[0]['class'] === Monolog\Handler\StreamHandler::class)
						&& (stripos($e->getMessage(), 'failed to open stream: permission denied') !== false)
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
				foreach (self::$shutdownHandlers as $handler)
				{
					try
					{
						$handler();
					}
					catch (Throwable $e)
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
					}
				}
			}
		);
	}
	
	public static function render(string $url)
	{
		$path = parse_url($url, PHP_URL_PATH);
		$path = rtrim($path, '/');
		
		if (empty($path))
		{
			$path = '/';
		}
		
		Router::render($path);
	}
}
