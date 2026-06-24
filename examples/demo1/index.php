<?php

// Startup Program

defined('HIYA_DEBUG') or define('HIYA_DEBUG', true);

require_once __DIR__ . '/../../autoload.php';

Hiya::run(__DIR__ . '/protected/config/web.php');
