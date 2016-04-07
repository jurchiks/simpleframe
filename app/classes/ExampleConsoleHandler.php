<?php
namespace app\classes;

use simpleframe\ConsoleHandler;

class ExampleConsoleHandler extends ConsoleHandler
{
	public static function foo(...$arguments)
	{
		var_dump('foo called', $arguments);
	}
}
