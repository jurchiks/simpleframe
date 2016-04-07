<?php
namespace app\classes;

use simpleframe\routing\Parameter;

class TestInjection implements Parameter
{
	private $someData;
	
	public function __construct()
	{
		$this->someData = md5(strval(microtime(true)));
	}
	
	public function getSomeData(): string
	{
		return $this->someData;
	}
}
