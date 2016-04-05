<?php
namespace classes;

use ConsoleHandler;

class ExampleConsoleHandler extends ConsoleHandler
{
	public static function foo(...$arguments)
	{
		var_dump('foo called', $arguments);
	}
}
