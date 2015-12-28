<?php
namespace responses;

class ExceptionResponse extends ErrorResponse
{
	public function __construct(\Throwable $e)
	{
		parent::__construct(self::getContent($e));
	}
	
	public function render()
	{
		parent::render();
	}
	
	private static function getContent(\Throwable $e)
	{
		$class = get_class($e);
		$trace = [];
		
		foreach ($e->getTrace() as $item)
		{
			$trace[] = '<li>'
				// exceptions thrown from within functions invoked via reflection don't have file & line in the function call trace
				. (isset($item['file'], $item['line']) ? $item['file'] . ' line ' . $item['line'] . '<br/>' : '')
				. (isset($item['class'], $item['type']) ? $item['class'] . $item['type'] : '') //
				. $item['function'] . '(' . self::getArgs($item['args']) . ');' //
				. '</li>';
		}
		
		$trace = implode('', $trace);
		
		return <<<CONTENT
<div>{$class} in {$e->getFile()} line {$e->getLine()}:</div>
<div>{$e->getMessage()}</div>
<div><ul>{$trace}</ul></div>
CONTENT;
	}
	
	private static function getArgs(array $args)
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
				$data[] = '[' . self::getArgs($arg) . ']';
			}
			else
			{
				$data[] = print_r($arg, true);
			}
		}
		
		return implode(', ', $data);
	}
}
