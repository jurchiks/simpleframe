<?php
abstract class ConsoleHandler
{
	/** @return callable[] */
	public static final function listMethods(): array
	{
		$callbacks = [];
		
		foreach (get_class_methods(static::class) as $method)
		{
			$callbacks[] = [static::class, $method];
		}
		
		return $callbacks;
	}
}
