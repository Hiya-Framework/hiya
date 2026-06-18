<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @since 1.0
 * 
 * This is autoload file, this file will inclue on your index.php
 * 
 */

defined('HIYA_DEBUG') or define('HIYA_DEBUG',false);
defined('YII_DEBUG') or define('YII_DEBUG',HIYA_DEBUG);

// Path constants
defined('HIYA_PATH') or define('HIYA_PATH', dirname(__FILE__));
defined('HIYA_VERSION') or define('HIYA_VERSION', '1.0-dev');

// Load YiiBase
if(!class_exists('YiiBase', false))
	require_once HIYA_PATH . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'yii' . DIRECTORY_SEPARATOR. 'YiiBase.php';


// ============ PREVENT Yii.php INCLUDE ============
// Create Yii class BEFORE loading YiiBase
if (!class_exists('Yii', false)) {
    class Yii extends YiiBase {}
}
// =================================================



/**
 * Hiya Framework Main Class
 */
class Hiya extends YiiBase
{
    /**
     * @var array Core components
     */
    public static $_coreComponents = [
        'session' => ['class' => 'Hiya\\Component\\Session'],
        'auth'   => ['class' => 'Hiya\\Component\\Auth'],
        'jwt'    => ['class' => 'Hiya\\Component\\Jwt'],
    ];
    
    /**
     * @var array Component instances
     */
    public static $_components = [];
    public static $_componentConfigs = [];
    public static $_bindings = [];
    public static $_instances = [];
    public static $_componentsInitialized = false;
    protected static $_webLogRouteRegistered = false;
    
    /**
     * Create Web Application
     */
    public static function createWebApplication($config = null)
    {
        $app = self::createApplication('CWebApplication', $config);
        self::registerComponentsToApp($app, self::$_coreComponents);
        
        if (defined('YII_DEBUG') && YII_DEBUG) {
            self::overrideErrorHandler($app);
            if (!self::isCli()) {
                self::injectWebLogRoute($app);
            }
        }
        
        return $app;
    }
    
    /**
     * Auto-detect and run appropriate application
     */
    public static function run($config)
    {
        if (self::isCli()) {
            $app = self::createConsoleApplication($config);
            $app->run();
            return;
        }

        $app = self::createWebApplication($config);
        $app->run();
    }

    /**
     * Create Console Application
     */
    public static function createConsoleApplication($config = null)
    {
        defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
        defined('HIYA_CONSOLE') or define('HIYA_CONSOLE', true);
        
        if (class_exists('Hiya\\Console\\Application')) {
            $app = new Hiya\Console\Application($config);
            self::registerComponentsToApp($app, self::$_coreComponents);
            return $app;
        }
        
        $app = self::createApplication('CConsoleApplication', $config);
        self::registerComponentsToApp($app, self::$_coreComponents);
        return $app;
    }
    
    /**
     * Register components to application
     */
    protected static function registerComponentsToApp($app, $components)
    {
        foreach ($components as $name => $config) {
            if (!$app->hasComponent($name)) {
                $app->setComponent($name, $config);
            }
        }
    }
    
    protected static function overrideErrorHandler($app)
    {
        // 1. Get the current configuration array from the application
        $config = $app->getComponent('errorHandler');
        
        // 2. If it's not configured, provide a default array
        if (!is_array($config)) {
            $config = [
                'class' => '\Hiya\Error\ErrorHandler',
                'errorAction' => 'site/error',
            ];
        }

        // 3. Ensure the class property is set (in case they forgot it in main.config)
        if (!isset($config['class'])) {
            $config['class'] = '\Hiya\Error\ErrorHandler';
        }

        // 4. Pass the ARRAY to Yii. Do NOT use "new $className()"
        // Yii will now use this array to instantiate the class when the error occurs.
        $app->setComponent('errorHandler', $config);
    }
    
    /**
     * Inject web log route
     */
    protected static function injectWebLogRoute($app)
    {
        try {
            $log = $app->getComponent('log');
            if (!$log) return;
            
            $routes = $log->getRoutes();
            $hasHiyaRoute = false;
            
            foreach ($routes as $route) {
                if ($route instanceof Hiya\Logging\WebLogRoute) {
                    $hasHiyaRoute = true;
                    break;
                }
            }
            
            if ($hasHiyaRoute) return;
            
            $webLogRoute = new Hiya\Logging\WebLogRoute();
            $webLogRoute->levels = 'error, warning, info, trace, profile';
            $webLogRoute->showMemory = true;
            $webLogRoute->maxLogEntries = 500;
            $webLogRoute->position = 'bottom';
            
            $routesArray = [];
            foreach ($routes as $route) {
                $routesArray[] = $route;
            }
            array_unshift($routesArray, $webLogRoute);
            $log->setRoutes($routesArray);
            
        } catch (Exception $e) {
            if (defined('YII_DEBUG') && YII_DEBUG) {
                error_log('Failed to inject HiyaWebLogRoute: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Check if CLI mode
     */
    protected static function isCli()
    {
        return php_sapi_name() === 'cli' || defined('STDIN');
    }
    
    /**
     * Register core components
     */
    public static function registerCoreComponents()
    {
        if (self::$_componentsInitialized) return;
        
        foreach (self::$_coreComponents as $name => $config) {
            self::setComponent($name, $config);
        }
        
        self::$_componentsInitialized = true;
    }
    
    /**
     * Set component
     */
    public static function setComponent($name, $config)
    {
        if (is_string($config)) {
            $config = ['class' => $config];
        }
        self::$_componentConfigs[$name] = $config;
        if (isset(self::$_components[$name])) {
            unset(self::$_components[$name]);
        }
    }
    
    /**
     * Get component
     */
    public static function getComponent($name, $createIfNotExists = true)
    {
        if (isset(self::$_components[$name])) {
            return self::$_components[$name];
        }
        
        if (!$createIfNotExists) return null;
        
        $config = self::$_componentConfigs[$name] ?? self::$_coreComponents[$name] ?? null;
        if ($config) {
            $component = self::createComponent($config);
            self::$_components[$name] = $component;
            return $component;
        }
        
        return null;
    }
    
    /**
     * Create component
     */
    public static function createComponent($config)
    {
        if (is_string($config)) {
            $class = $config;
            $properties = [];
        } else {
            $class = $config['class'];
            $properties = $config;
            unset($properties['class']);
        }
        
        if (!class_exists($class)) {
            $nsClass = 'Hiya\\Components\\' . $class;
            if (class_exists($nsClass)) {
                $class = $nsClass;
            } else {
                throw new CException("Component class '$class' not found");
            }
        }
        
        $component = new $class();
        foreach ($properties as $key => $value) {
            if (property_exists($component, $key)) {
                $component->$key = $value;
            }
        }
        
        if (method_exists($component, 'init')) {
            $component->init();
        }
        
        return $component;
    }
    
    /**
     * Dump and die
     */
    public static function dd($data)
    {
        if (defined('YII_DEBUG') && YII_DEBUG) {
            echo '<pre>';
            var_dump($data);
            echo '</pre>';
        }
        exit;
    }
    
    /**
     * Get version
     */
    public static function getVersion()
    {
        return HIYA_VERSION;
    }
    
    /**
     * Get application instance
     */
    public static function app()
    {
        return parent::app();
    }
    
    /**
     * Get path of alias
     */
    public static function getPathOfAlias($alias)
    {
        return parent::getPathOfAlias($alias);
    }
    
    /**
     * Import alias
     */
    public static function import($alias, $forceInclude = false)
    {
        return parent::import($alias, $forceInclude);
    }
}

// Register core components
Hiya::registerCoreComponents();