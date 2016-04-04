<?php
namespace classes;

use routing\exceptions\RouteParameterException;
use routing\UrlParameter;

class User extends UrlParameter
{
	// this would usually be in database
	private static $users = [
		1 => 'admin'
	];
	private $id;
	private $name;
	
	public function __construct(string $value)
	{
		if (!isset(self::$users[intval($value)]))
		{
			throw new RouteParameterException('Invalid user ID');
		}
		
		$this->id = intval($value);
		$this->name = self::$users[$this->id];
	}
	
	public function __toString(): string
	{
		return strval($this->id);
	}
	
	public static function getPattern(): string
	{
		return '\d+';
	}
	
	public function getId(): int
	{
		return $this->id;
	}
	
	public function getName(): string
	{
		return $this->name;
	}
}
