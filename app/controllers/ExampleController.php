<?php
namespace controllers;

use classes\User;
use Controller;
use Logger;
use Request;
use responses\JsonResponse;

class ExampleController extends Controller
{
	public static function index(Request $request)
	{
		Logger::log(Logger::INFO, 'example log message');
		
		return 'route: ' . $request->getPath() . ', next up: ' . self::route('example.test2', ['user' => new User(1)], ['foo' => 'bar']);
	}
	
	public static function put(Request $request)
	{
		return new JsonResponse($request->getData());
	}
	
	public static function test()
	{
		return self::error(self::template('example', ['hello' => 'world']), 503);
	}
	
	public static function test2(User $user, int $id2 = null)
	{
		return $user . ':' . $id2;
	}
}
