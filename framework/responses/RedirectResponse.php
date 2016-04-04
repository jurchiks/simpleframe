<?php
namespace responses;

class RedirectResponse implements Response
{
	private $url;
	private $isPermanent;
	
	public function __construct(string $url, bool $isPermanent = false)
	{
		$this->url = $url;
		$this->isPermanent = $isPermanent;
	}
	
	public function render()
	{
		http_response_code($this->isPermanent ? 301 : 302);
		header('Location: ' . $this->url);
		die();
	}
}
