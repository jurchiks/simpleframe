<?php
namespace responses;

use App;

class TemplateResponse extends TextResponse
{
	private $template;
	
	public function __construct(string $name, array $data = [])
	{
		$this->template = App::getTemplatingEngine()->getTemplate($name, $data);
	}
	
	public function render()
	{
		echo $this->template->render();
	}
}
