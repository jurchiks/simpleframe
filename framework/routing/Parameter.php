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
}
