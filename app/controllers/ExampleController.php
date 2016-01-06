<?php
namespace controllers;

use Controller;
use Logger;

class ExampleController extends Controller
{
	public static function index()
	{
		Logger::log(Logger::INFO, 'example log message');
		
		return 'index! ' . self::route('test2', ['id' => 1], ['foo' => 'bar']);
	}
	
	public static function test()
	{
		return self::error(self::template('example', ['hello' => 'world']), 503);
	}
	
	public static function test2(int $id, int $id2 = null)
	{
		return $id . ':' . $id2;
	}
}
