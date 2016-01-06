<?php
namespace responses;

use InvalidArgumentException;

class TextResponse implements Response
{
	protected $content;
	
	/**
	 * @param string|TextResponse $content : the content to display
	 */
	public function __construct($content)
	{
		if (is_string($content) || ($content instanceof TextResponse))
		{
			$this->content = $content;
		}
		else
		{
			throw new InvalidArgumentException(sprintf('Invalid content type %s', gettype($content)));
		}
	}
	
	public function render()
	{
		if (is_string($this->content))
		{
			echo $this->content;
		}
		else if ($this->content instanceof TextResponse)
		{
			$this->content->render();
		}
	}
}
