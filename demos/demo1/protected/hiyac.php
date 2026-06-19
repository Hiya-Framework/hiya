<?php
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

$HiyaRoot = dirname(__DIR__) . '/../..';
require_once($HiyaRoot . '/autoload.php');

// Register Hiya\Console namespace
Hiya::setPathOfAlias('Hiya.Console', $HiyaRoot . '/src/Console');

Hiya::setPathOfAlias('application', __DIR__);

$config = require(__DIR__ . '/config/console.php');
Hiya::run($config);