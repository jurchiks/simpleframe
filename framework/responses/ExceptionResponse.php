<?php
namespace responses;

use Exception;
use Throwable;

class ExceptionResponse extends ErrorResponse
{
	/**
	 * @param Exception|Throwable $e
	 */
	public function __construct($e)
	{
		// TODO HHVM support for Throwable required
		parent::__construct(self::makeContent($e));
	}
	
	public function render()
	{
		parent::render();
	}
	
	private static function makeContent($e): string
	{
		/** @var Exception|Throwable $e */
		$class = get_class($e);
		$lines = [
			"{$class} in {$e->getFile()} on line {$e->getLine()}:",
			$e->getMessage(),
			self::makeTrace($e),
		];
		
		if (PHP_SAPI === 'cli')
		{
			return implode(PHP_EOL, $lines);
		}
		else
		{
			return '<div>' . implode('</div><div>', $lines) . '</div>';
		}
	}
	
	private static function makeTrace($e): string
	{
		/** @var Exception|Throwable $e */
		$trace = [];
		
		foreach ($e->getTrace() as $item)
		{
			// exceptions thrown from within functions invoked via reflection don't have source data in the function call trace
			$source = (isset($item['file'], $item['line']) //
				? $item['file'] . ' line ' . $item['line'] //
				: 'Reflection invocation');
			$call = (isset($item['class'], $item['type']) ? $item['class'] . $item['type'] : '') //
				. $item['function'] . '(' . self::makeArgs($item['args']) . ');';
			
			if (PHP_SAPI === 'cli')
			{
				$trace[] = "\t* " . $source . PHP_EOL . "\t  " . $call . PHP_EOL;
			}
			else
			{
				$trace[] = '<li>' . $source . '<br/>' . $call . '</li>';
			}
		}
		
		$trace = implode('', $trace);
		
		if (PHP_SAPI !== 'cli')
		{
			$trace = '<ul>' . $trace . '</ul>';
		}
		
		return $trace;
	}
	
	private static function makeArgs(array $args): string
	{
		$data = [];
		
		foreach ($args as $arg)
		{
			if (is_object($arg))
			{
				$data[] = get_class($arg);
			}
			else if (is_array($arg))
			{
				$data[] = '[' . self::makeArgs($arg) . ']';
			}
			else if (is_string($arg))
			{
				$data[] = '"' . $arg . '"';
			}
			else
			{
				$data[] = print_r($arg, true);
			}
		}
		
		return implode(', ', $data);
	}
}
