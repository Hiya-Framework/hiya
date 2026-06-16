<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Error\ErrorHandler
 * @since 1.0
 */

namespace Hiya\Error;

use Yii;
use CErrorHandler;

class ErrorHandler extends CErrorHandler
{
    /**
     * @var string theme for error page (light, dark, auto)
     */
    public $theme = 'auto';
    
    /**
     * @var boolean show detailed error info in debug mode
     */
    public $detailedErrors = true;
    
    /**
     * @var string custom error view path
     */
    public $errorViewPath = null;
    
    /**
     * @var array custom error templates
     */
    public $errorTemplates = [];
    
    /**
     * @var boolean log errors to file
     */
    public $logErrors = true;
    
    /**
     * @var string error log file path
     */
    public $errorLogFile = '';
    
    /**
     * Initialize error handler
     */
    public function init()
    {
        parent::init();
        
        // Set error log file
        if (empty($this->errorLogFile)) {
            $this->errorLogFile = Yii::getPathOfAlias('application.runtime') . '/logs/error.log';
        }
        
        // Ensure log directory exists
        $logDir = dirname($this->errorLogFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }
    
    /**
     * Handle error and display appropriate view
     * @param object $event CErrorEvent
     */
    protected function handleError($event)
    {
        // Log error if enabled
        if ($this->logErrors) {
            $this->logErrorMessage($event);
        }
        
        parent::handleError($event);
    }
    
    /**
     * Handle exception
     * @param object $event CExceptionEvent
     */
    protected function handleException($event)
    {
        // Get exception from event
        $exception = null;
        if (property_exists($event, 'exception')) {
            $exception = $event->exception;
        }
        
        // Log exception if enabled and exception exists
        if ($this->logErrors && $exception !== null) {
            $this->logExceptionMessage($exception);
        }
        
        parent::handleException($event);
    }
    
    /**
     * Render error view - OVERRIDE to use custom views
     * @param string $view
     * @param array $data
     */
    protected function render($view, $data)
    {
        $errorCode = isset($data['code']) ? $data['code'] : 500;
        $errorType = isset($data['type']) ? $data['type'] : 'Error';
        $errorMessage = isset($data['message']) ? $data['message'] : 'An error occurred';
        $errorFile = isset($data['file']) ? $data['file'] : 'Unknown';
        $errorLine = isset($data['line']) ? $data['line'] : '?';
        
        // Prepare additional data
        $errorData = [
            'code' => $errorCode,
            'type' => $errorType,
            'message' => $errorMessage,
            'file' => $errorFile,
            'line' => $errorLine,
            'errorType' => $this->getErrorType($errorCode),
            'theme' => $this->theme,
            'detailedErrors' => $this->detailedErrors && YII_DEBUG,
            'timestamp' => date('Y-m-d H:i:s'),
            'memoryUsage' => $this->formatBytes(memory_get_usage(true)),
            'peakMemory' => $this->formatBytes(memory_get_peak_usage(true)),
            'executionTime' => round(microtime(true) - (defined('YII_BEGIN_TIME') ? YII_BEGIN_TIME : ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))), 4),
        ];
        
        // Add stack trace in debug mode
        if (YII_DEBUG && $this->detailedErrors) {
            $errorData['trace'] = $this->getStackTrace();
            $errorData['serverInfo'] = $this->getServerInfo();
            $errorData['requestInfo'] = $this->getRequestInfo();
        }
        
        // Determine which view to use
        $isDebug = YII_DEBUG && $this->detailedErrors && isset($errorData['trace']);
        $viewName = $isDebug ? 'debug' : 'error';
        
        // Find view file
        $viewFile = $this->findViewFile($viewName, $errorCode);
        
        if ($viewFile && file_exists($viewFile)) {
            extract($errorData);
            include($viewFile);
        } else {
            // Ultimate fallback - simple error message
            $this->renderSimpleError($errorData);
        }
    }
    
    /**
     * Find view file in various locations
     * @param string $viewName
     * @param int $errorCode
     * @return string|false
     */
    protected function findViewFile($viewName, $errorCode)
    {
        // 1. Check custom template for specific error code
        if (isset($this->errorTemplates[$errorCode])) {
            $customFile = $this->errorTemplates[$errorCode];
            if (file_exists($customFile)) {
                return $customFile;
            }
        }
        
        // 2. Check custom error view path
        if ($this->errorViewPath) {
            $customFile = $this->errorViewPath . '/' . $viewName . '.php';
            if (file_exists($customFile)) {
                return $customFile;
            }
            
            $customCodeFile = $this->errorViewPath . '/' . $errorCode . '.php';
            if (file_exists($customCodeFile)) {
                return $customCodeFile;
            }
        }
        
        // 3. Check application views
        $appBase = Yii::getPathOfAlias('application.views');
        if ($appBase) {
            $appView = $appBase . '/error/' . $viewName . '.php';
            if (file_exists($appView)) {
                return $appView;
            }
            
            $appCodeView = $appBase . '/error/' . $errorCode . '.php';
            if (file_exists($appCodeView)) {
                return $appCodeView;
            }
        }
        
        // 4. Check Hiya core views
        $HiyaView = dirname(__FILE__) . '/views/' . $viewName . '.php';
        if (file_exists($HiyaView)) {
            return $HiyaView;
        }
        
        $HiyaCodeView = dirname(__FILE__) . '/views/' . $errorCode . '.php';
        if (file_exists($HiyaCodeView)) {
            return $HiyaCodeView;
        }
        
        return false;
    }
    
    /**
     * Simple error fallback
     * @param array $data
     */
    protected function renderSimpleError($data)
    {
        $isDebug = YII_DEBUG && $this->detailedErrors;
        $statusCode = $data['code'];
        
        http_response_code($statusCode);
        
        if ($isDebug) {
            echo "<!DOCTYPE html>
            <html>
            <head><title>Debug Error - {$statusCode}</title></head>
            <body style='font-family:monospace;padding:20px;background:#1e1e2e;color:#e0e0e0;'>
            <h1 style='color:#ef4444'>Debug Error: {$statusCode}</h1>
            <p><strong>Type:</strong> {$data['type']}</p>
            <p><strong>Message:</strong> {$data['message']}</p>
            <p><strong>File:</strong> {$data['file']}</p>
            <p><strong>Line:</strong> {$data['line']}</p>
            </body>
            </html>";
        } else {
            echo "<!DOCTYPE html>
            <html>
            <head><title>Error {$statusCode}</title></head>
            <body style='font-family:sans-serif;text-align:center;padding:50px;'>
            <h1>Error {$statusCode}</h1>
            <p>Sorry, something went wrong.</p>
            </body>
            </html>";
        }
    }
    
    /**
     * Log error message
     * @param object $event
     */
    protected function logErrorMessage($event)
    {
        $code = isset($event->code) ? $event->code : 500;
        $message = isset($event->message) ? $event->message : 'Unknown error';
        $file = isset($event->file) ? $event->file : 'Unknown';
        $line = isset($event->line) ? $event->line : 0;
        
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $this->getErrorType($code),
            $message,
            $file,
            $line
        );
        
        error_log($logMessage, 3, $this->errorLogFile);
    }
    
    /**
     * Log exception message
     * @param \Exception $exception
     */
    protected function logExceptionMessage($exception)
    {
        if (!$exception) {
            return;
        }
        
        $message = sprintf(
            "[%s] Exception: %s: %s in %s:%d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        error_log($message, 3, $this->errorLogFile);
    }
    
    /**
     * Get error type from HTTP code
     * @param int $code
     * @return string
     */
    protected function getErrorType($code)
    {
        $types = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];
        return isset($types[$code]) ? $types[$code] : 'Error';
    }
    
    /**
     * Get stack trace
     * @return array
     */
    protected function getStackTrace()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $result = [];
        $skipFrames = 6;
        
        foreach (array_slice($trace, $skipFrames, 30) as $frame) {
            $result[] = [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
        }
        return $result;
    }
    
    /**
     * Get server information
     * @return array
     */
    protected function getServerInfo()
    {
        return [
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'Server Port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
        ];
    }
    
    /**
     * Get request information
     * @return array
     */
    protected function getRequestInfo()
    {
        $request = Yii::app()->getRequest();
        return [
            'Request URI' => $request->getRequestUri(),
            'Request Method' => $request->getRequestType(),
            'IP Address' => $request->getUserHostAddress(),
            'User Agent' => substr($request->getUserAgent(), 0, 100),
        ];
    }
    
    /**
     * Format bytes
     * @param int $bytes
     * @return string
     */
    protected function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}