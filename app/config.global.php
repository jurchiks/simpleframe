<?php
return [
	'environment' => 'production',
	'debug'       => false,
	'timezone'    => 'Europe/Riga',
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
