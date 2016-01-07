<?php
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FilterHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonoLogger;

class Logger
{
	const DEBUG = MonoLogger::DEBUG;
	const INFO = MonoLogger::INFO;
	const NOTICE = MonoLogger::NOTICE;
	const WARNING = MonoLogger::WARNING;
	const ERROR = MonoLogger::ERROR;
	const CRITICAL = MonoLogger::CRITICAL;
	const ALERT = MonoLogger::ALERT;
	const EMERGENCY = MonoLogger::EMERGENCY;
	
	public static function make($name)
	{
		return new MonoLogger($name);
	}
	
	/**
	 * @param int $logLevel : one of the Logger constants
	 * @param string[] $messages : the messages to log
	 */
	public static function log(int $logLevel, string ...$messages)
	{
		self::getDefaultLogger()->log($logLevel, implode(PHP_EOL, $messages));
	}
	
	private static function getDefaultLogger()
	{
		static $appLogger = null;
		
		if (is_null($appLogger))
		{
			$appLogger = new MonoLogger(Config::get('app.name'));
			$formatter = new LineFormatter("[%datetime%] %message%\n", null, true); // allow newlines
			
			foreach ($appLogger->getLevels() as $name => $level)
			{
				$mainHandler = new StreamHandler(LOG_DIR . '/' . strtolower($name) . '.log', $level, true, 0664);
				
				$mainHandler->setFormatter($formatter);
				$appLogger->pushHandler(new FilterHandler($mainHandler, $level, $level));
			}
		}
		
		return $appLogger;
	}
}
