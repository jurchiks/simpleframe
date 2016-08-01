<?php
namespace app\controllers;

use app\classes\TestInjection;
use app\classes\User;
use js\tools\commons\http\Request;
use simpleframe\Controller;
use simpleframe\Logger;
use simpleframe\responses\ErrorResponse;
use simpleframe\responses\JsonResponse;
use simpleframe\responses\TemplateResponse;
use simpleframe\routing\Router;

class ExampleController extends Controller
{
	public static function index(Request $request, TestInjection $ti)
	{
		Logger::log(Logger::INFO, 'example log message');
		
		return 'path: ' . $request->getUri()->getPath() . ', random injection data: ' . $ti->getSomeData() . ', next up: '
			. Router::link(
				'example.test2',
				['user' => new User(1)]
			)
			->setQueryParameters(['foo' => 'b a r'])
			->getAbsolute();
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
