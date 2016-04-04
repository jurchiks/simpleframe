<?php
class EventHandler
{
	/** Callback arguments: string $name, array $handlerArguments */
	const ON_ROUTE_MATCH = 'on_route_match';
	
	/** @var callable[][] */
	private static $eventCallbacks = [];
	
	public static function on(string $event, callable $callback)
	{
		self::$eventCallbacks[$event][] = $callback;
	}
	
	public static function off(string $event, callable $callback = null)
	{
		if (is_null($callback))
		{
			unset(self::$eventCallbacks[$event]);
		}
		else if (!empty(self::$eventCallbacks[$event]))
		{
			self::$eventCallbacks[$event] = array_filter(
				self::$eventCallbacks[$event],
				function (callable $val) use ($callback)
				{
					return ($val !== $callback);
				}
			);
		}
	}
	
	public static function trigger(string $event, ...$data)
	{
		if (isset(self::$eventCallbacks[$event]))
		{
			foreach (self::$eventCallbacks[$event] as $callback)
			{
				$callback(...$data);
			}
		}
	}
}
