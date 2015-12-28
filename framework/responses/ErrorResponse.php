<?php
namespace responses;

class ErrorResponse extends TextResponse
{
	private $code;
	
	/**
	 * @param string|TextResponse $message : the error message to display
	 * @param int $code : the HTTP response code
	 */
	public function __construct($message, int $code = 500)
	{
		if (!\Config::getBool('debug'))
		{
			// only show the full error message in debug environments
			$message = '<h1>Internal server error</h1>';
			$code = 500;
		}
		
		parent::__construct($message);
		
		$this->code = $code;
	}
	
	public function render()
	{
		http_response_code($this->code);
		parent::render();
	}
}
