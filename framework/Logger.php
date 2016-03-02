<?php
use js\tools\commons\logging\FileLogger;

class Logger
{
	const DEBUG = FileLogger::DEBUG;
	const INFO = FileLogger::INFO;
	const NOTICE = FileLogger::NOTICE;
	const WARNING = FileLogger::WARNING;
	const ERROR = FileLogger::ERROR;
	const CRITICAL = FileLogger::CRITICAL;
	const FATAL = FileLogger::FATAL;
	
	/**
	 * @param int $logLevel : one of the Logger constants
	 * @param string $message : the message to log
	 */
	public static function log(int $logLevel, string $message)
	{
		self::getDefaultLogger()->log($logLevel, $message);
	}
	
	private static function getDefaultLogger()
	{
		static $appLogger = null;
		
		if (is_null($appLogger))
		{
			$appLogger = new FileLogger(LOG_DIR);
		}
		
		return $appLogger;
	}
}
