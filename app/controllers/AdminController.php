<?php
namespace controllers;

use classes\Session;
use Controller;
use Request;
use responses\RedirectResponse;

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
			return new RedirectResponse($request->getReferer() ?: self::route('admin.index'));
		}
		
		if ($request->isMethod('post'))
		{
			$error = Session::login($request->getData());
			
			if (!$error)
			{
				return new RedirectResponse($request->getReferer() ?: self::route('admin.index'));
			}
		}
		else
		{
			$error = '';
		}
		
		return self::template('admin/login', ['error' => $error]);
	}
	
	public static function logout()
	{
		Session::logout();
		
		return new RedirectResponse(self::route('admin.login'));
	}
}
