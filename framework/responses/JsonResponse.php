<?php
namespace responses;

use InvalidArgumentException;
use JsonSerializable;

class JsonResponse extends TextResponse
{
	private $options;
	
	/**
	 * @param string|array|object|JsonSerializable $content
	 * @param int $options : the options to provide for json_encode()
	 * @see json_encode
	 */
	public function __construct($content, int $options = 0)
	{
		if (is_string($content)
			|| is_array($content)
			|| is_object($content))
		{
			$this->content = $content;
		}
		else if ($content instanceof JsonSerializable)
		{
			$this->content = $content->jsonSerialize();
		}
		else
		{
			throw new InvalidArgumentException(sprintf('Unsupported content type %s', gettype($content)));
		}
		
		$this->options = $options;
	}
	
	public function render()
	{
		header('Content-type: application/json');
		echo json_encode($this->content, $this->options);
	}
}
