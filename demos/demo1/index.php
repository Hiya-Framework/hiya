<?php

// Startup Program

defined('HIYA_DEBUG') or define('HIYA_DEBUG', true);

require __DIR__ . '/../../../hiya/autoload.php';

Hiya::run(__DIR__ . '/protected/config/web.php');
