<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

define('HIYA_VERSION', '0.0.1-alpha');
defined('HIYA_DEBUG') or define('HIYA_DEBUG', false);
defined('HIYA_PATH') or define('HIYA_PATH', __DIR__ . '/hiya');
defined('HIYA_LIB_PATH') or define('HIYA_LIB_PATH', HIYA_PATH . '/lib');

defined('YII_DEBUG') or define('YII_DEBUG', HIYA_DEBUG);

// here we can choose yii mode: full use full package of yii1 framework
if (getenv('HIYA_MODE') === 'full') {
    defined('HIYA_LIB_YII_PATH_FULL') or define('HIYA_LIB_YII_PATH_FULL', HIYA_LIB_PATH . '/yii-full');
    define('HIYA_LIB_YII_PATH', HIYA_LIB_YII_PATH_FULL);
} else {
    define('HIYA_LIB_YII_PATH', HIYA_LIB_PATH . '/yii');
}

defined('HIYA_SRC_PATH') or define('HIYA_SRC_PATH', HIYA_PATH . '/src');

/**
 * Simple autoloader for Yii1 & Hiya
 * load files and directory automatically use autoload
 */
spl_autoload_register(function($className) {
    // ===== YII1 CORE =====
    if (strpos($className, '\\') === false) {
        $file = HIYA_LIB_YII_PATH . '/' . $className . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
        
        $folders = ['base', 'web', 'collections', 'i18n', 'web/actions', 'web/filters', 'web/auth', 'web/helpers', 'db', 'validators', 'caching'];
        foreach ($folders as $folder) {
            $file = HIYA_LIB_YII_PATH . '/' . $folder . '/' . $className . '.php';
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // Hiya\Web\Application -> Web/Application.php
    // Hiya\Base\Component -> Base/Component.php
    // Hiya\Component\Session -> Component/Session.php
    if (strpos($className, 'Hiya\\') === 0) {
        $relativeClass = substr($className, 5); // "Hiya\" = 5 chars
        $file = HIYA_SRC_PATH . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
    
    if (isset(YiiBase::$coreClasses[$className])) {
        require_once HIYA_LIB_YII_PATH . YiiBase::$coreClasses[$className];
        return;
    }
});

require_once HIYA_LIB_YII_PATH . '/YiiBase.php';
require_once HIYA_LIB_YII_PATH . '/yii.php';
require_once __DIR__ . '/hiya/Base.php';

if (!class_exists('Yii', false)) {
    class Yii extends YiiBase {}
}

if (!class_exists('Hiya', false)) {   
    class Hiya extends HiyaBase
    {      
    }
}