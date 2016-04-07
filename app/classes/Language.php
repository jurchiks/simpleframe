<?php
namespace app\classes;

use simpleframe\Config;
use simpleframe\routing\UrlParameter;

class Language extends UrlParameter
{
	private $language;
	
	public function __construct(string $value)
	{
		$this->language = $value;
	}
	
	public function __toString(): string
	{
		return $this->language;
	}
	
	public function getValue()
	{
		return $this->language;
	}
	
	public static function getPattern(): string
	{
		return implode('|', Config::get('app.languages.allowed'));
	}
	
	public static function isOptional(): bool
	{
		return true;
	}
	
	public static function getDefault(): UrlParameter
	{
		return new static(Config::get('app.languages.default'));
	}
}
