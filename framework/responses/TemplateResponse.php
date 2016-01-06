<?php
namespace responses;

class TemplateResponse extends TextResponse
{
	private $file;
	private $data;
	
	public function __construct(string $name, array $data = [])
	{
		if (file_exists(ROOT_DIR . '/app/templates/' . $name . '.phtml'))
		{
			// enable overloading of default framework templateS
			$this->file = ROOT_DIR . '/app/templates/' . $name . '.phtml';
		}
		else if (file_exists(ROOT_DIR . '/framework/templates/' . $name . '.phtml'))
		{
			$this->file = ROOT_DIR . '/framework/templates/' . $name . '.phtml';
		}
		else
		{
			throw new \InvalidArgumentException('Invalid template name ' . $name);
		}
		
		$this->data = $data;
	}
	
	public function render()
	{
		extract($this->data, EXTR_SKIP);
		include $this->file;
	}
}
