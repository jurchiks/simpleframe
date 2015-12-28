<?php
use responses\ErrorResponse;
use responses\ExceptionResponse;
use responses\TemplateResponse;
use routing\exceptions\RouteNotFoundException;
use routing\Router;

final class App
{
	private static $shutdownHandlers = [];
	
	public static function registerShutdownHandler(callable $handler)
	{
		self::$shutdownHandlers[] = $handler;
	}
	
	public static function init()
	{
		set_exception_handler(
			function ($e)
			{
				if ($e instanceof RouteNotFoundException)
				{
					// exception for a special exception; if a user mistypes a URL, show a 404 not found page.
					(new ErrorResponse(new TemplateResponse('page_not_found'), 404))->render();
				}
				else
				{
					Logger::log(Logger::CRITICAL, strval($e));
					
					(new ExceptionResponse($e))->render();
				}
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
					// ignore notice level errors, continue as usual
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
