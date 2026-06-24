<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Base;

/**
 * Hiya Error Handler — Override Yii1 CErrorHandler
 * 
 * @package Hiya\Base
 * @since 1.0
 */
class ErrorHandler extends \CErrorHandler
{
    /**
     * @var string Theme for error page
     */
    public $theme = 'auto';
    
    /**
     * @var bool Show detailed errors
     */
    public $detailedErrors = true;
    
    /**
     * @var string Custom error view path
     */
    public $errorViewPath = null;
    
    /**
     * Render error view — Override untuk fix path issue
     */
    protected function render($view, $data)
    {
        $isDebug = YII_DEBUG && $this->detailedErrors;
        $viewName = $isDebug ? 'debug' : 'error';
        
        // Find view file dengan path yang aman
        $viewFile = $this->findViewFile($viewName);
        
        if ($viewFile && file_exists($viewFile)) {
            extract($data);
            include($viewFile);
            return;
        }
        
        // Fallback: render simple error
        $this->renderSimpleError($data);
    }
    
    /**
     * Find view file di berbagai lokasi
     */
    protected function findViewFile($viewName)
    {
        // 1. Custom path
        if ($this->errorViewPath) {
            $customFile = $this->errorViewPath . '/' . $viewName . '.php';
            if (file_exists($customFile)) {
                return $customFile;
            }
        }
        
        // 2. Application views
        $appView = \Yii::getPathOfAlias('application.views');
        if ($appView) {
            $viewFile = $appView . '/error/' . $viewName . '.php';
            if (file_exists($viewFile)) {
                return $viewFile;
            }
        }
        
        // 3. Hiya views
        if (defined('HIYA_SRC_PATH')) {
            $viewFile = HIYA_SRC_PATH . '/Base/views/' . $viewName . '.php';
            if (file_exists($viewFile)) {
                return $viewFile;
            }
        }
        
        // 4. Yii system views (dengan fallback)
        $systemView = \Yii::getPathOfAlias('system.views');
        if ($systemView) {
            $viewFile = $systemView . '/' . $viewName . '.php';
            if (file_exists($viewFile)) {
                return $viewFile;
            }
        }
        
        return null;
    }
    
    /**
     * Simple error fallback
     */
    protected function renderSimpleError($data)
    {
        $code = $data['code'] ?? 500;
        $message = $data['message'] ?? 'An error occurred';
        $file = $data['file'] ?? 'Unknown';
        $line = $data['line'] ?? '?';
        
        http_response_code($code);
        
        echo '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Error ' . $code . '</title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        background: #f0f2f5;
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 20px;
                        color: #333333;
                    }
                    .error-card {
                        background: #ffffff;
                        border-radius: 8px;
                        padding: 35px 40px;
                        width: 70%;
                        border: 1px solid #d0d7de;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                    }
                    .error-code {
                        font-size: 48px;
                        font-weight: 700;
                        color: #d32f2f;
                        line-height: 1;
                        margin-bottom: 8px;
                    }
                    .error-title {
                        font-size: 20px;
                        font-weight: 600;
                        color: #333;
                        margin-bottom: 16px;
                    }
                    .error-message {
                        background: #f8f9fa;
                        border-radius: 4px;
                        padding: 14px 18px;
                        border-left: 3px solid #d32f2f;
                        font-size: 14px;
                        color: #333;
                        margin-bottom: 16px;
                        word-break: break-word;
                        font-family: monospace;
                    }
                    .error-details {
                        font-size: 14px;
                        color: #555;
                        margin-bottom: 16px;
                        padding: 10px 14px;
                        background: #f8f9fa;
                        border-radius: 4px;
                        display: flex;
                        flex-wrap: wrap;
                        gap: 12px 24px;
                    }
                    .error-details strong {
                        color: #222;
                    }
                    .error-details span {
                        display: inline-block;
                    }
                    .error-trace {
                        background: #f8f9fa;
                        border-radius: 4px;
                        padding: 14px 18px;
                        font-family: monospace;
                        font-size: 12px;
                        color: #555;
                        max-height: 150px;
                        overflow: auto;
                        line-height: 1.6;
                        white-space: pre-wrap;
                        word-break: break-word;
                        margin-bottom: 20px;
                        border: 1px solid #e0e0e0;
                    }
                    .error-trace::-webkit-scrollbar { width: 5px; }
                    .error-trace::-webkit-scrollbar-track { background: #f8f9fa; }
                    .error-trace::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
                    .error-footer {
                        margin-top: 16px;
                        padding-top: 14px;
                        border-top: 1px solid #e0e0e0;
                        font-size: 12px;
                        color: #999;
                        text-align: center;
                    }
                    .error-footer a {
                        color: #666;
                        text-decoration: none;
                    }
                    .error-footer a:hover {
                        color: #333;
                        text-decoration: underline;
                    }
                    @media (max-width: 500px) {
                        .error-card { padding: 20px; }
                        .error-code { font-size: 36px; }
                        .error-details { flex-direction: column; gap: 6px; }
                    }
                </style>
            </head>
            <body>
                <div class="error-card">
                    <div class="error-code">' . $code . '</div>
                    <div class="error-title">Something went wrong</div>
                    <div class="error-message">' . htmlspecialchars($message) . '</div>
                    <div class="error-details">
                        <span><strong>File:</strong> ' . htmlspecialchars($file) . '</span>
                        <span><strong>Line:</strong> ' . $line . '</span>
                    </div>
                    <div class="error-trace">' . htmlspecialchars($data["trace"] ?? "No stack trace available") . '</div>
                    <div class="error-footer">
                        Hiya Framework develop by <a href="https://www.taktikspace.com/hiya" target="_blank">taktikspace.com/hiya</a>
                    </div>
                </div>
            </body>
            </html>';
    }
}