<?php
namespace responses;

use js\tools\commons\templating\Engine;

class TemplateResponse extends TextResponse
{
	private $template;
	
	public function __construct(string $name, array $data = [])
	{
		static $engine = null;
		
		if ($engine === null)
		{
			$engine = new Engine(ROOT_DIR . '/app/templates/');
			$engine->addRoot(ROOT_DIR . '/framework/templates/');
		}
		
		$this->template = $engine->getTemplate($name, $data);
	}
	
	public function render()
	{
		echo $this->template->render();
	}
}
