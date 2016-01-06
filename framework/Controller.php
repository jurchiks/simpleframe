<?php
use responses\ErrorResponse;
use responses\TemplateResponse;
use routing\Router;

abstract class Controller
{
	protected function route(string $name, array $namedParameters = [], array $getParams = [], bool $urlEncoded = false)
	{
		if (empty($getParams))
		{
			$query = '';
		}
		else
		{
			$query = '?' . http_build_query($getParams, '', ($urlEncoded ? '&amp;' : '&'));
		}
		
		return Router::link($name, $namedParameters) . $query;
	}
	
	protected function template(string $name, array $data = [])
	{
		return new TemplateResponse($name, $data);
	}
	
	protected function error($message, int $code = 500)
	{
		return new ErrorResponse($message, $code);
	}
}
