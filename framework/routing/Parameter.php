<?php
namespace routing;

use routing\exceptions\RouteParameterException;

interface Parameter
{
	/**
	 * @param string $value : the matched URL fragment
	 * @throws RouteParameterException if the value is invalid
	 */
	public function __construct(string $value);
	
	/**
	 * Get the string representation of this parameter in a URL.
	 * This value must be identical to the value received in the constructor.
	 */
	public function __toString(): string;
}
