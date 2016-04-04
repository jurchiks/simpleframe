<?php

use js\tools\commons\upload\UploadedFileCollection;

class Request
{
	private $method;
	private $path;
	private $data;
	private $files;
	private $referer;
	
	public function __construct(string $method, string $route, array $data, string $referer)
	{
		$path = substr($route, 0, strpos($route, '?') ?: strlen($route));
		$path = '/' . trim($path, '/');
		
		$this->method = $method;
		$this->path = $path;
		$this->data = new Data($data);
		$this->files = new UploadedFileCollection($_FILES);
		$this->referer = $referer;
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
	public function isMethod(string $method): bool
	{
		return (strcasecmp($this->method, $method) === 0);
	}
	
	/**
	 * Get the path part of the request URL.
	 *
	 * @return string e.g. /foo/bar
	 */
	public function getPath(): string
	{
		return $this->path;
	}
	
	/**
	 * Retrieve request data.
	 */
	public function getData(): Data
	{
		return $this->data;
	}
	
	/**
	 * Retrieve uploaded files.
	 */
	public function getFiles(): UploadedFileCollection
	{
		return $this->files;
	}
	
	public function getReferer(): string
	{
		return $this->referer;
	}
}
