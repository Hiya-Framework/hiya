<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Logging
 * @since 1.0
 */

namespace Hiya\Logging;

use Yii;
use CWebLogRoute;
use CLogger;
use Hiya;

/**
 * HiyaWebLogRoute - Enhanced Web Log Route for Hiya Framework
 * Automatically replaces default Yii logger
 *
 * @package Hiya.Logging
 * @since 1.0
 */
class WebLogRoute extends CWebLogRoute
{
    /**
     * @var string theme for log viewer (light, dark, auto)
     */
    public $theme = 'auto';
    
    /**
     * @var boolean show memory usage
     */
    public $showMemory = true;
    
    /**
     * @var boolean show execution time
     */
    public $showTime = true;
    
    /**
     * @var int maximum log entries to display
     */
    public $maxLogEntries = 500;
    
    /**
     * @var array filter by log levels
     */
    public $filterLevels = ['error', 'warning', 'info', 'trace', 'profile'];
    
    /**
     * @var boolean enable search functionality
     */
    public $enableSearch = true;
    
    /**
     * @var boolean enable copy to clipboard
     */
    public $enableCopy = true;
    
    /**
     * @var boolean enable auto refresh
     */
    public $autoRefresh = false;
    
    /**
     * @var int auto refresh interval in milliseconds
     */
    public $refreshInterval = 5000;
    
    /**
     * @var boolean show stack trace
     */
    public $showStackTrace = true;
    
    /**
     * @var boolean collapse logs by default
     */
    public $collapsedByDefault = false;
    
    /**
     * @var string log panel position
     */
    public $position = 'bottom';
    
    /**
     * @var bool whether this route is enabled
     */
    public $enabled = true;

    public $levels;
    
    /**
     * Initialize the route
     */
    public function init()
    {
        parent::init();
        
        // Auto-register as default logger when in debug mode
        if (YII_DEBUG && $this->enabled) {
            $this->registerAsDefaultLogger();
        }
    }
    
    /**
     * Register as default logger by replacing existing routes
     */
    protected function registerAsDefaultLogger()
    {
        $log = Yii::app()->getComponent('log');
        if ($log) {
            $routes = $log->getRoutes();
            $hasHiyaRoute = false;
            
            foreach ($routes as $route) {
                if ($route instanceof self) {
                    $hasHiyaRoute = true;
                    break;
                }
            }
            
            if (!$hasHiyaRoute) {
                // Convert routes to array if it's not
                $routesArray = [];
                foreach ($routes as $route) {
                    $routesArray[] = $route;
                }
                // Add this route at the beginning
                array_unshift($routesArray, $this);
                $log->setRoutes($routesArray);
            }
        }
    }
    
    /**
     * Check if current request is API
     * @return bool
     */
    protected function isApiRequest()
    {
        if (method_exists(\Hiya::app()->request, 'isApiRequest')) {
            return \Hiya::app()->request->isApiRequest();
        }
        
        $controller = \Yii::app()->getController();
        if ($controller) {
            if ($controller instanceof \Hiya\Base\ApiController) {
                return true;
            }
            if (property_exists($controller, 'isApi') && $controller->isApi === true) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Displays the log messages with enhanced formatting.
     * @param array $logs list of log messages
     */
    public function processLogs($logs)
    {
        if ($this->isApiRequest()) {
            Hiya::log('API request detected, skipping debug bar', 'info', 'debug');
            return;
        }
        
        // Only display in non-AJAX, non-Flash requests
        $app = Yii::app();
        $isAjax = $app->getRequest()->getIsAjaxRequest();
        $isFlash = $app->getRequest()->getIsFlashRequest();
        
        if ($isAjax || $isFlash) {
            return;
        }
        
        // Filter logs by max entries
        if (count($logs) > $this->maxLogEntries) {
            $logs = array_slice($logs, -$this->maxLogEntries);
        }
        
        // Process logs for display
        $processedLogs = $this->processLogEntries($logs);
        
        // Collect additional info
        $appInfo = $this->getApplicationInfo();
        
        // Render using external view
        $this->renderView([
            'logs' => $processedLogs,
            'appInfo' => $appInfo,
            'config' => [
                'theme' => $this->theme,
                'showMemory' => $this->showMemory,
                'showTime' => $this->showTime,
                'enableSearch' => $this->enableSearch,
                'enableCopy' => $this->enableCopy,
                'autoRefresh' => $this->autoRefresh,
                'refreshInterval' => $this->refreshInterval,
                'showStackTrace' => $this->showStackTrace,
                'collapsedByDefault' => $this->collapsedByDefault,
                'position' => $this->position,
                'filterLevels' => $this->filterLevels
            ]
        ]);
    }
    
    /**
     * Process log entries for better display
     * @param array $logs raw log entries
     * @return array processed log entries
     */
    protected function processLogEntries($logs)
    {
        $processed = [];
        $request = Yii::app()->getRequest();
        $startTime = defined('YII_BEGIN_TIME') ? YII_BEGIN_TIME : ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        
        foreach ($logs as $index => $log) {
            $logTime = (float)$log[3];
            $relativeTime = round(($logTime - $startTime) * 1000, 2);
            
            $entry = [
                'id' => $index,
                'level' => $this->getLevelName($log[1]),
                'level_code' => $log[1],
                'level_icon' => $this->getLevelIcon($log[1]),
                'category' => $log[2],
                'message_raw' => $log[0],
                'message' => $this->formatMessage($log[0]),
                'message_type' => $this->getMessageType($log[0]),
                'time' => $log[3],
                'time_formatted' => date('Y-m-d H:i:s', (int)$log[3]),
                'relative_time' => $relativeTime,
                'memory' => $log[4] ?? 0,
                'memory_formatted' => $this->formatBytes($log[4] ?? 0),
                'url' => $request->getRequestUri(),
                'method' => $request->getRequestType(),
                'ip' => $request->getUserHostAddress(),
            ];
            
            $processed[] = $entry;
        }
        
        return $processed;
    }
    
    /**
     * Get Operating System information
     * @return string
     */
    protected function getOperatingSystem()
    {
        $os = PHP_OS;
        $osName = '';
        
        switch (true) {
            case strpos($os, 'WIN') !== false:
                $osName = 'Windows';
                if (function_exists('php_uname')) {
                    $version = php_uname('r');
                    $osName .= ' ' . $version;
                }
                break;
            case strpos($os, 'Linux') !== false:
                $osName = 'Linux';
                if (file_exists('/etc/os-release')) {
                    $release = @parse_ini_file('/etc/os-release');
                    if ($release && isset($release['PRETTY_NAME'])) {
                        $osName = $release['PRETTY_NAME'];
                    }
                } else {
                    $osName .= ' ' . php_uname('r');
                }
                break;
            case strpos($os, 'Darwin') !== false:
                $osName = 'macOS';
                $version = php_uname('r');
                $osName .= ' ' . $version;
                break;
            case strpos($os, 'FreeBSD') !== false:
                $osName = 'FreeBSD';
                break;
            case strpos($os, 'OpenBSD') !== false:
                $osName = 'OpenBSD';
                break;
            case strpos($os, 'NetBSD') !== false:
                $osName = 'NetBSD';
                break;
            case strpos($os, 'SunOS') !== false:
                $osName = 'Solaris';
                break;
            default:
                $osName = $os;
        }
        
        $arch = php_uname('m');
        if ($arch) {
            $osName .= ' (' . $arch . ')';
        }
        
        return $osName;
    }
    
    /**
     * Get Server Software information
     * @return string
     */
    protected function getServerSoftware()
    {
        $server = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        
        if (strpos($server, 'Apache') !== false) {
            preg_match('/Apache\/(\d+\.\d+)/', $server, $matches);
            $version = isset($matches[1]) ? ' ' . $matches[1] : '';
            return 'Apache' . $version;
        }
        if (strpos($server, 'nginx') !== false) {
            preg_match('/nginx\/(\d+\.\d+\.\d+)/', $server, $matches);
            $version = isset($matches[1]) ? ' ' . $matches[1] : '';
            return 'Nginx' . $version;
        }
        if (strpos($server, 'Microsoft-IIS') !== false) {
            return 'IIS';
        }
        
        return $server;
    }
    
    /**
     * Get application information
     * @return array
     */
    protected function getApplicationInfo()
    {
        $request = Yii::app()->getRequest();
        $hiyaVersion = defined('HIYA_VERSION') ? HIYA_VERSION : '1.0.0';
        
        return [
            'name' => Yii::app()->name,
            'environment' => YII_DEBUG ? 'Development' : 'Production',
            'php_version' => PHP_VERSION,
            'hiya_version' => $hiyaVersion,
            'operating_system' => $this->getOperatingSystem(),
            'server_software' => $this->getServerSoftware(),
            'url' => $request->getRequestUri(),
            'method' => $request->getRequestType(),
            'ip' => $request->getUserHostAddress(),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'execution_time' => round(microtime(true) - (defined('YII_BEGIN_TIME') ? YII_BEGIN_TIME : ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))), 4),
        ];
    }
    
    /**
     * Format message with syntax highlighting
     * @param string $message raw message
     * @return string formatted message
     */
    protected function formatMessage($message)
    {
        $json = json_decode($message, true);
        if ($json !== null && json_last_error() === JSON_ERROR_NONE) {
            return '<pre class="hiya-log-json">' . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</pre>';
        }
        
        if (preg_match('/^(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|TRUNCATE|REPLACE)/i', trim($message))) {
            return '<pre class="hiya-log-sql">' . htmlspecialchars($message) . '</pre>';
        }
        
        if (strpos($message, '#0 ') !== false || preg_match('/\s+at\s+[\w\\\\]+::/', $message)) {
            return '<pre class="hiya-log-trace">' . htmlspecialchars($message) . '</pre>';
        }
        
        return nl2br(htmlspecialchars($message));
    }
    
    /**
     * Get message type
     * @param string $message
     * @return string
     */
    protected function getMessageType($message)
    {
        if (json_decode($message, true) !== null) {
            return 'json';
        }
        if (preg_match('/^(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP)/i', trim($message))) {
            return 'sql';
        }
        if (strpos($message, '#0 ') !== false) {
            return 'trace';
        }
        return 'text';
    }
    
    /**
     * Get level name
     * @param int $level
     * @return string
     */
    protected function getLevelName($level)
    {
        $levels = [
            CLogger::LEVEL_ERROR => 'error',
            CLogger::LEVEL_WARNING => 'warning',
            CLogger::LEVEL_INFO => 'info',
            CLogger::LEVEL_TRACE => 'trace',
            CLogger::LEVEL_PROFILE => 'profile',
        ];
        
        return isset($levels[$level]) ? $levels[$level] : 'unknown';
    }
    
    /**
     * Get level icon
     * @param int $level
     * @return string
     */
    protected function getLevelIcon($level)
    {
        $icons = [
            CLogger::LEVEL_ERROR => '🔴',
            CLogger::LEVEL_WARNING => '🟡',
            CLogger::LEVEL_INFO => '🔵',
            CLogger::LEVEL_TRACE => '⚪',
            CLogger::LEVEL_PROFILE => '🟢',
        ];
        
        return $icons[$level] ?? '📝';
    }
    
    /**
     * Format bytes
     * @param int $bytes
     * @return string
     */
    protected function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
    
    /**
     * Render view from external file
     * @param array $data
     */
    protected function renderView($data)
    {
        $viewPaths = [
            Yii::getPathOfAlias('application.views.log') . '/hiya-log.php',
            dirname(__FILE__) . '/views/hiya-log.php',
            null
        ];
        
        $viewFile = null;
        foreach ($viewPaths as $path) {
            if ($path && file_exists($path)) {
                $viewFile = $path;
                break;
            }
        }
        
        if ($viewFile) {
            extract($data);
            include($viewFile);
        } else {
            $this->renderInlineView($data);
        }
    }
    
    /**
     * Render inline view (fallback)
     * @param array $data
     */
    protected function renderInlineView($data)
    {
        $logs = $data['logs'];
        $appInfo = $data['appInfo'];
        $config = $data['config'];
        ?>
        <div class="hiya-debug-bar" style="position:fixed;bottom:0;left:0;right:0;background:#1e1e2e;color:#e0e0e0;font-family:monospace;font-size:12px;z-index:99999;border-top:2px solid #3b82f6;max-height:300px;overflow:auto;">
            <div style="background:#2a2a3e;padding:8px;font-weight:bold;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span>🔍 Hiya Debug Console (<?php echo count($logs); ?> logs)</span>
                <div style="display:flex;gap:15px;flex-wrap:wrap;">
                    <span>🖥️ OS: <?php echo htmlspecialchars($appInfo['operating_system'] ?? 'Unknown'); ?></span>
                    <span>🌐 Server: <?php echo htmlspecialchars($appInfo['server_software'] ?? 'Unknown'); ?></span>
                    <span>🐘 PHP: <?php echo $appInfo['php_version']; ?></span>
                    <span>🚀 Hiya: <?php echo $appInfo['hiya_version']; ?></span>
                    <span>💾 Mem: <?php echo $appInfo['memory_usage']; ?></span>
                    <button onclick="this.parentElement.parentElement.style.display='none'" style="background:#3e3e5e;border:none;color:#fff;padding:2px 8px;cursor:pointer;">×</button>
                </div>
            </div>
            <table style="width:100%;border-collapse:collapse;">
                <thead style="background:#25253a;">
                    <tr><th style="padding:6px;text-align:left;">Level</th><th style="padding:6px;text-align:left;">Time</th><th style="padding:6px;text-align:left;">Category</th><th style="padding:6px;text-align:left;">Message</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr style="border-bottom:1px solid #3e3e5e;">
                        <td style="padding:6px;color:<?php echo $log['level'] == 'error' ? '#ef4444' : ($log['level'] == 'warning' ? '#f59e0b' : '#3b82f6'); ?>"><?php echo strtoupper($log['level']); ?></td>
                        <td style="padding:6px;"><?php echo date('H:i:s', strtotime($log['time_formatted'])); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($log['category']); ?></td>
                        <td style="padding:6px;"><?php echo $log['message']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}