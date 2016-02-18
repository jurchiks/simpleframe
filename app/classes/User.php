<?php
namespace classes;

use routing\exceptions\RouteParameterException;
use routing\Parameter;

class User implements Parameter
{
	// this would usually be in database
	private static $users = [
		1 => 'admin'
	];
	private $id;
	private $name;
	
	public function __construct(string $value)
	{
		if (!is_numeric($value) || !isset(self::$users[intval($value)]))
		{
			throw new RouteParameterException('Invalid user ID');
		}
		
		$this->id = intval($value);
		$this->name = self::$users[$this->id];
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
