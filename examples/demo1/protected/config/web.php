<?php
return array(
    'basePath' => dirname(__FILE__) . '/..',
    'name' => 'Hiya Framework',

	'preload'=>array('log'),

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
		'application.components.*',
	),

	'controllerMap' => array(
        'site' => 'App\\Controllers\\SiteController',
    ),

	'defaultController'=>'site',

	// application components
	'components'=>array(

		'user'=>array(
			'allowAutoLogin'=>true,
		),

		'request' => array(
			'enableCsrfValidation' => true,
		),
		
		'errorHandler'=>array(
			// use 'site/error' action to display errors
			'errorAction'=>'site/error',
		),

		'urlManager'=>array(
			'urlFormat'=>'path',
        	'showScriptName'=>false,
			'rules'=>array(
				'/'	=> 'site/index',
				'/about'	=> 'site/about',

				'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
		),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
				// uncomment the following to show log messages on web pages
				/*
				array(
					'class'=>'CWebLogRoute',
				),
				*/
			),
		),
	),
);