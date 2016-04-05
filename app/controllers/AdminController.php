<?php
namespace controllers;

use classes\Session;
use Controller;
use Request;
use responses\RedirectResponse;
use responses\TemplateResponse;
use routing\Router;

class AdminController extends Controller
{
	public static function index()
	{
		return 'welcome to the other side!';
	}
	
	public static function login(Request $request)
	{
		if (Session::isLoggedIn())
		{
			return new RedirectResponse($request->getReferer() ?: Router::link('admin.index'));
		}
		
		if ($request->isMethod('post'))
		{
			$error = Session::login($request->getData());
			
			if (!$error)
			{
				return new RedirectResponse($request->getReferer() ?: Router::link('admin.index'));
			}
		}
		else
		{
			$error = '';
		}
		
		return new TemplateResponse('admin/login', ['error' => $error]);
	}
	
	public static function logout()
	{
		Session::logout();
		
		return new RedirectResponse(Router::link('admin.login'));
	}
}
