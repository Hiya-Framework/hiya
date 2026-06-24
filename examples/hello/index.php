<?php

require_once __DIR__ . '/../../autoload.php';

$config = [
    'id' => 'hiya-test',
    'name' => 'Hiya Test Application',
    'basePath' => __DIR__ . '/protected',
    'controllerMap' => array(
        'site' => 'App\\Controllers\\SiteController',
    ),
    
    'components' => [
        'urlManager' => [
            'urlFormat' => 'path',
            'showScriptName' => false,
        ],
        'errorHandler' => [
            'class' => 'Hiya\\Base\\ErrorHandler', 
            'detailedErrors' => true,
        ],
    ],
];

Hiya::run($config);
