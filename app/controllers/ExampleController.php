<?php
namespace controllers;

use Controller;
use Logger;
use Request;
use responses\JsonResponse;

class ExampleController extends Controller
{
	public static function index(Request $request)
	{
		Logger::log(Logger::INFO, 'example log message');
		
		return 'route: ' . $request->getPath() . ', next up: ' . self::route('example.test2', ['id' => 1], ['foo' => 'bar']);
	}
	
	public static function put(Request $request)
	{
		return new JsonResponse($request->getData());
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
