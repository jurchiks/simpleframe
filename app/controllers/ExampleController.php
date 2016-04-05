<?php
namespace controllers;

use classes\TestInjection;
use classes\User;
use Controller;
use Logger;
use Request;
use responses\ErrorResponse;
use responses\JsonResponse;
use responses\TemplateResponse;
use routing\Router;

class ExampleController extends Controller
{
	public static function index(Request $request, TestInjection $ti)
	{
		Logger::log(Logger::INFO, 'example log message');
		
		return 'route: ' . $request->getPath() . ', random injection data: ' . $ti->getSomeData() . ', next up: '
		. Router::link(
			'example.test2',
			['user' => new User(1)],
			['foo' => 'bar']
		);
	}
	
	public static function put(Request $request)
	{
		return new JsonResponse($request->getData());
	}
	
	public static function test()
	{
		return new ErrorResponse(new TemplateResponse('example', ['hello' => 'world']), 503);
	}
	
	public static function test2(User $user, int $id2 = null)
	{
		return $user . ':' . $id2;
	}
}
