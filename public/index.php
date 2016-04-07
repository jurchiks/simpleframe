<?php
if (!file_exists(__DIR__ . '/../vendor/autoload.php'))
{
	throw new RuntimeException('Execute `composer install` in project root directory');
}

require __DIR__ . '/../vendor/autoload.php';

\simpleframe\App::run(realpath(__DIR__ . '/../'));
