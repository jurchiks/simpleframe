<?php
namespace routing;

use routing\exceptions\RouteParameterException;

abstract class UrlParameter implements Parameter
{
	/**
	 * @param string $value : the matched URL fragment
	 * @throws RouteParameterException if the value is invalid
	 */
	public abstract function __construct(string $value);
	/**
	 * Get the string representation of this parameter in a URL.
	 * This value must be acceptable by the constructor.
	 */
	public abstract function __toString(): string;
	
	/**
	 * Get the regex pattern for this parameter for use in routes.
	 */
	public static function getPattern(): string
	{
		return '[^/]+';
	}
	
	public static function isOptional(): bool
	{
		return false;
	}
	
	/**
	 * Get the default value for this parameter if it is optional.
	 * 
	 * @throws RouteParameterException if no default value is available
	 */
	public static function getDefault(): UrlParameter
	{
		throw new RouteParameterException('No default value available');
	}
}
