<?php
return [
	'environment' => 'production',
	'debug'       => false,
	'timezone'    => 'Europe/Riga',
	'host'        => 'dom.ain',
	'app'         => [
		'name'      => 'your-app-name',
		'crypto'    => [
			'secret' => 'your-secret-random-string',
			'prefix' => '',
		],
		'languages' => [
			'allowed' => ['en', 'ru', 'lv'],
			'default' => 'lv',
		],
	],
];
