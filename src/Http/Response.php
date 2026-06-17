<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Http
 * @since 1.0
 */

namespace Hiya\Http;

/**
 * Standard HTTP Response
 *
 * Features:
 * - JSON, HTML, Plain Text, XML responses
 * - File download with automatic MIME type detection
 * - File streaming with chunked transfer
 * - X-Sendfile support (nginx/Apache)
 * - Cache control headers
 * - Fluent interface for easy chaining
 */
class Response extends AbstractResponse
{
    /**
     * @var string Response format (html, json, plain, xml)
     */
    protected $format = 'html';

    /**
     * @var array Data for formatting
     */
    protected $data = [];

    /**
     * @var array cURL info (for backward compatibility)
     */
    protected $info = [];

    /**
     * Constructor
     *
     * @param mixed $body Response body
     * @param int $statusCode HTTP status code
     * @param array $info cURL info
     */
    public function __construct($body = null, $statusCode = 200, $info = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->info = $info;
    }

    // ============ STATUS CHECKS ============

    /**
     * Check if response is successful (2xx)
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is client error (4xx)
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is server error (5xx)
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Check if response is redirect (3xx)
     *
     * @return bool
     */
    public function isRedirect()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Get status message
     *
     * @return string
     */
    public function getStatusMessage()
    {
        $messages = [
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        return $messages[$this->statusCode] ?? 'Unknown';
    }

    /**
     * Get response body as JSON array
     * This is for RESPONSE body parsing, NOT for request JSON
     *
     * @return array|null
     */
    public function getJson()
    {
        if (is_string($this->body)) {
            return json_decode($this->body, true);
        }
        return null;
    }

    /**
     * Get response body as array
     *
     * @return array
     */
    public function toArray()
    {
        $data = $this->getJson();
        return is_array($data) ? $data : [];
    }

    /**
     * Get cURL info
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    // ============ FLUENT METHODS ============

    /**
     * Send as JSON
     *
     * @param mixed $data Data to encode as JSON
     * @return $this
     */
    public function json($data = null)
    {
        $this->format = 'json';
        $this->withHeader('Content-Type', 'application/json');
        $this->body = json_encode($data ?: $this->data);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON encoding error: ' . json_last_error_msg());
        }

        return $this;
    }

    /**
     * Send as HTML
     *
     * @param string|null $content HTML content
     * @return $this
     */
    public function html($content = null)
    {
        $this->format = 'html';
        $this->withHeader('Content-Type', 'text/html; charset=utf-8');
        $this->body = $content ?: $this->body;
        return $this;
    }

    /**
     * Send as Plain Text
     *
     * @param string|null $content Text content
     * @return $this
     */
    public function plain($content = null)
    {
        $this->format = 'plain';
        $this->withHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->body = $content ?: $this->body;
        return $this;
    }

    /**
     * Send as XML
     *
     * @param mixed $data Data to convert to XML
     * @param string $root Root element name
     * @return $this
     */
    public function xml($data = null, $root = 'response')
    {
        $this->format = 'xml';
        $this->withHeader('Content-Type', 'application/xml; charset=utf-8');

        $data = $data ?: $this->data;
        $this->body = $this->arrayToXml($data, $root);

        return $this;
    }

    // ============ FILE HANDLING ============

    /**
     * Send file as download (force download)
     */
    public function download($filePath, $fileName = null, $mimeType = null)
    {
        $this->validateFile($filePath);

        $fileName = $fileName ?: basename($filePath);
        $fileSize = filesize($filePath);
        $mimeType = $mimeType ?: $this->getMimeType($filePath);
        $safeFileName = $this->sanitizeFileName($fileName);

        $this->withHeader('Content-Type', $mimeType);
        $this->withHeader('Content-Disposition', 'attachment; filename="' . $safeFileName . '"');
        $this->withHeader('Content-Length', (string) $fileSize);
        $this->withHeader('Content-Transfer-Encoding', 'binary');
        $this->withHeader('Cache-Control', 'private, max-age=0, must-revalidate');
        $this->withHeader('Pragma', 'public');
        $this->withHeader('Expires', '0');

        $this->body = fopen($filePath, 'rb');

        return $this;
    }

    /**
     * Send file inline (display in browser)
     */
    public function file($filePath, $fileName = null, $mimeType = null)
    {
        $this->validateFile($filePath);

        $fileName = $fileName ?: basename($filePath);
        $fileSize = filesize($filePath);
        $mimeType = $mimeType ?: $this->getMimeType($filePath);
        $safeFileName = $this->sanitizeFileName($fileName);

        $this->withHeader('Content-Type', $mimeType);
        $this->withHeader('Content-Disposition', 'inline; filename="' . $safeFileName . '"');
        $this->withHeader('Content-Length', (string) $fileSize);
        $this->withHeader('Cache-Control', 'public, max-age=86400');

        $this->body = fopen($filePath, 'rb');

        return $this;
    }

    /**
     * Send file using X-Sendfile (nginx/Apache)
     */
    public function xSendFile($filePath, $fileName = null, $mimeType = null)
    {
        $this->validateFile($filePath);

        $fileName = $fileName ?: basename($filePath);
        $mimeType = $mimeType ?: $this->getMimeType($filePath);
        $safeFileName = $this->sanitizeFileName($fileName);

        $this->withHeader('Content-Type', $mimeType);
        $this->withHeader('Content-Disposition', 'attachment; filename="' . $safeFileName . '"');

        if (function_exists('apache_get_modules') && in_array('mod_xsendfile', apache_get_modules())) {
            $this->withHeader('X-Sendfile', $filePath);
        } else {
            $this->withHeader('X-Accel-Redirect', $filePath);
        }

        return $this;
    }

    // ============ REDIRECT ============

    public function redirect($url, $statusCode = 302)
    {
        $this->withStatus($statusCode);
        $this->withHeader('Location', $url);
        return $this;
    }

    public function back($default = '/')
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $default;
        return $this->redirect($referer);
    }

    public function route($route, $params = [])
    {
        if (class_exists('\\Yii')) {
            $url = \Yii::app()->createUrl($route, $params);
            return $this->redirect($url);
        }
        return $this->redirect($route);
    }

    // ============ CACHE CONTROL ============

    public function cache($maxAge = 3600, $isPublic = true)
    {
        $this->withHeader('Cache-Control', ($isPublic ? 'public' : 'private') . ', max-age=' . $maxAge);
        return $this;
    }

    public function noCache()
    {
        $this->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->withHeader('Pragma', 'no-cache');
        $this->withHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        return $this;
    }

    // ============ SERVICE INTEGRATION ============

    public function service($result, $serviceName = null)
    {
        $data = [
            'success' => true,
            'data' => $result,
        ];

        if ($serviceName) {
            $data['service'] = $serviceName;
        }

        return $this->json($data);
    }

    public function serviceError($message, $code = 500, $serviceName = null)
    {
        $this->withStatus($code);

        $data = [
            'success' => false,
            'error' => $message,
            'code' => $code,
        ];

        if ($serviceName) {
            $data['service'] = $serviceName;
        }

        return $this->json($data);
    }

    // ============ HELPERS ============

    public function data($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getFormat()
    {
        return $this->format;
    }

    // ============ PROTECTED HELPERS ============

    protected function validateFile($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \RuntimeException("File not readable: {$filePath}");
        }
    }

    protected function getMimeType($filePath)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return $mimeType;
        }

        if (function_exists('mime_content_type')) {
            return mime_content_type($filePath);
        }

        return $this->getMimeTypeByExtension($filePath);
    }

    protected function getMimeTypeByExtension($filePath)
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
            'rar' => 'application/vnd.rar',
            '7z' => 'application/x-7z-compressed',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
        ];

        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }

    protected function sanitizeFileName($fileName)
    {
        $fileName = str_replace(['/', '\\'], '', $fileName);
        $fileName = preg_replace('/[\x00-\x1F]/', '', $fileName);

        if (preg_match('/[^\x20-\x7E]/', $fileName)) {
            return rawurlencode($fileName);
        }

        return $fileName;
    }

    protected function arrayToXml($data, $root = 'response')
    {
        if (!is_array($data) && !is_object($data)) {
            return (string) $data;
        }

        $xml = new \SimpleXMLElement('<' . $root . ' xmlns="http://www.w3.org/2001/XMLSchema"/>');
        $this->arrayToXmlRecursive($data, $xml);
        return $xml->asXML();
    }

    protected function arrayToXmlRecursive($data, &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item' . $key;
            }

            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXmlRecursive($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }

    // ============ SEND ============

    protected function sendBody()
    {
        if (is_callable($this->body)) {
            $callback = $this->body;
            $callback();
        } elseif (is_resource($this->body)) {
            while (!feof($this->body)) {
                echo fread($this->body, 8192);

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
            fclose($this->body);
        } else {
            echo (string) $this->body;
        }
    }

    // ============ STATIC FACTORIES ============

    public static function create($body = null, $status = 200)
    {
        return new static($body, $status);
    }

    public static function jsonResponse($data, $status = 200)
    {
        return static::create()->withStatus($status)->json($data);
    }

    public static function htmlResponse($content, $status = 200)
    {
        return static::create()->withStatus($status)->html($content);
    }

    public static function errorResponse($message, $status = 500)
    {
        return static::create()
            ->withStatus($status)
            ->json(['error' => $message, 'code' => $status]);
    }

    public static function serviceResponse($result, $serviceName = null)
    {
        return static::create()->service($result, $serviceName);
    }

    public static function serviceErrorResponse($message, $code = 500, $serviceName = null)
    {
        return static::create()->serviceError($message, $code, $serviceName);
    }

    public static function downloadResponse($filePath, $fileName = null)
    {
        return static::create()->download($filePath, $fileName);
    }

    public static function redirectResponse($url, $status = 302)
    {
        return static::create()->redirect($url, $status);
    }
}