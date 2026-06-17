<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @since 1.0
 * 
 * This is autoload file, this file will inclue on your index.php
 */

// Path constants
defined('HIYA_PATH') or define('HIYA_PATH', __DIR__);

// Load Composer autoloader
$composerAutoload = HIYA_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Load Hiya main class
require_once HIYA_PATH . DIRECTORY_SEPARATOR . 'hiya.php';

// Simple autoloader
spl_autoload_register(function ($className) {
    // Hiya classes
    if (strpos($className, 'Hiya\\') === 0) {
        $relativeClass = substr($className, 4);
        $classFile = str_replace('\\', '/', $relativeClass);

        $paths = [
            HIYA_PATH . '/src/Base/' . $classFile . '.php',           // Base classes
            HIYA_PATH . '/src/' . $classFile . '.php',                // Root src
            HIYA_PATH . '/src/Components/' . basename($classFile) . '.php', // Components
            HIYA_PATH . '/src/Auth/' . $classFile . '.php',           // Auth
            HIYA_PATH . '/src/Web/' . $classFile . '.php',           // Web
            HIYA_PATH . '/src/Services/' . $classFile . '.php',           // Services
            HIYA_PATH . '/src/Http/' . $classFile . '.php',           // Http
            HIYA_PATH . '/src/Queue/' . $classFile . '.php',          // Queue
            HIYA_PATH . '/src/Security/' . $classFile . '.php',       // Security
            HIYA_PATH . '/src/Helpers/' . $classFile . '.php',       // Helpers
            HIYA_PATH . '/src/Logging/' . $classFile . '.php',        // Logging
            HIYA_PATH . '/src/Logging/views/' . $classFile . '.php',  // Logging views
            HIYA_PATH . '/src/Error/' . $classFile . '.php',        // Error
            HIYA_PATH . '/src/Error/views/' . $classFile . '.php',  // Error views
        ];        

        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return true;
            }
        }
        
        $simplePath = HIYA_PATH . '/src/' . basename($classFile) . '.php';
        if (file_exists($simplePath)) {
            require_once $simplePath;
            return true;
        }

        if (defined('YII_DEBUG') && YII_DEBUG) {
            error_log("Autoload: Class {$className} not found in Hiya paths");
        }
        
        return false;
    }
    
    // App classes (your application)
    if (strpos($className, 'App\\') === 0) {
        $relativeClass = substr($className, 4);
        $classFile = str_replace('\\', '/', $relativeClass);
        $path = HIYA_PATH . '/protected/' . $classFile . '.php';
        if (file_exists($path)) {
            require_once $path;
            return true;
        }
        return false;
    }
    
    return false;
});

// Register shutdown function to ensure logs are flushed
register_shutdown_function(function() {
    if (class_exists('Yii', false) && Yii::app()) {
        $logger = Yii::getLogger();
        if ($logger) {
            $logger->flush(true);
        }
    }
});

// Register core components
Hiya::registerCoreComponents();