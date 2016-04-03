<?php

class Request
{
	private $method;
	private $path;
	private $data;
	
	public function __construct(string $method, string $route, array $data)
	{
		$path = parse_url($route, PHP_URL_PATH);
		$path = rtrim($path, '/') ?: '/';
		
		$this->method = $method;
		$this->path = $path;
		$this->data = $data;
	}
	
	/**
	 * Get the request method.
	 *
	 * @return string one of [get, post, put, delete, head, options]
	 */
	public function getMethod()
	{
		return $this->method;
	}
	
	/**
	 * Compare the request method.
	 *
	 * @param string $method one of [get, post, put, delete, head, options]
	 * @return bool true if the method matches, false otherwise
	 */
	public function isMethod(string $method)
	{
		return (strcasecmp($this->method, $method) === 0);
	}
	
	/**
	 * Get the path part of the request URL.
	 *
	 * @return string e.g. /foo/bar
	 */
	public function getPath()
	{
		return $this->path;
	}
	
	/**
	 * Retrieve parameter(s) passed in the request.
	 *
	 * @param string $key : the name of the parameter to get; if not specified, all parameters are returned
	 * @return string[]|string|null an array containing all parameters, the value of the specified parameter
	 * or null if the parameter does not exist
	 */
	public function getData(string $key = null)
	{
		if (is_null($key))
		{
			return $this->data;
		}
		
		return ($this->data[$key] ?? null);
	}
}
