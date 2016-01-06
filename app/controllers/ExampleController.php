<?php
namespace controllers;

use Controller;
use Logger;

class ExampleController extends Controller
{
	public function index()
	{
		Logger::log(Logger::INFO, 'example log message');
		
		return 'index! ' . $this->route('test2', ['id' => 1], ['foo' => 'bar']);
	}
	
	public function test()
	{
		return $this->error($this->template('example', ['hello' => 'world']), 503);
	}
	
	public function test2(int $id, int $id2 = null)
	{
		return $id . ':' . $id2;
	}
}
