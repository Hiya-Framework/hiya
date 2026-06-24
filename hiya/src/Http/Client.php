<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */


namespace Hiya\Http;

/**
 * HTTP Client - Native cURL implementation
 * Extends CApplicationComponent for Yii1 integration
 *
 * Features:
 * - GET, POST, PUT, DELETE, PATCH, HEAD
 * - Headers, Timeout, SSL
 * - Bearer Token, Basic Auth
 * - Response parsing (JSON, Array)
 * - File download
 * - Multi-request (parallel)
 * - Stats and logging
 * - Yii1 Component (accessible via Yii::app()->client)
 * - Backward compatibility with Yii1 property naming
 * - Backward compatibility with php-curl-class (setOpt, etc.)
 *
 * Example:
 * ```php
 * // As Yii1 component
 * Yii::app()->client->setBearerToken('token');
 * $response = Yii::app()->client->get('https://api.example.com/users');
 *
 * // As standalone
 * $client = new Client();
 * $response = $client->post('https://api.example.com/login', [
 *     'username' => 'user',
 *     'password' => 'pass'
 * ]);
 * ```
 */
class Client extends \CApplicationComponent
{
    // ============ CONFIGURATION PROPERTIES ============

    /**
     * @var array Default options
     */
    public $defaultOptions = [
        'timeout' => 30,
        'connect_timeout' => 10,
        'user_agent' => 'Hiya HttpClient/1.0',
        'verify_ssl' => false,
        'follow_location' => true,
        'max_redirects' => 5,
    ];

    /**
     * @var array Current options
     */
    protected $options = [];

    /**
     * @var array Headers
     */
    protected $headers = [];

    /**
     * @var array Cookies
     */
    protected $cookies = [];

    /**
     * @var array Last request info
     */
    protected $lastInfo = [];

    /**
     * @var array Middleware stack
     */
    protected $middleware = [];

    /**
     * @var bool Enable logging
     */
    public $enableLogging = false;

    /**
     * @var callable Logger callback
     */
    protected $logger;

    /**
     * @var array Stats
     */
    protected $stats = [
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
        'total_time' => 0,
    ];

    /**
     * @var resource|object cURL multi handle (resource in PHP 7, CurlMultiHandle in PHP 8+)
     */
    protected $multiCurl;

    /**
     * @var array Multi-request queue
     */
    protected $multiQueue = [];

    /**
     * @var array Backward compatibility for CURLOPT constants (from setOpt)
     */
    protected $_curlOptions = [];

    /**
     * @var array Backward compatibility for response properties
     */
    protected $_lastResponse;

    // ============ BACKWARD COMPATIBILITY PROPERTIES ============

    /**
     * @var int Timeout in seconds (backward compatibility)
     */
    public $timeout = 30;

    /**
     * @var int Connect timeout in seconds (backward compatibility)
     */
    public $connectTimeout = 10;

    /**
     * @var string User agent (backward compatibility)
     */
    public $userAgent = 'Hiya Framework';

    /**
     * @var bool Verify SSL (backward compatibility)
     * @deprecated Use verify_ssl in defaultOptions instead
     */
    public $verifySSL = false;

    /**
     * @var mixed Last response (backward compatibility)
     */
    public $response;

    /**
     * @var int HTTP status code (backward compatibility)
     */
    public $httpStatusCode;

    /**
     * @var string Error message (backward compatibility)
     */
    public $error;

    /**
     * @var int Error code (backward compatibility)
     */
    public $errorCode;

    // ============ INITIALIZATION ============

    /**
     * Initialize component
     */
    public function init()
    {
        parent::init();

        // Apply backward compatibility properties
        $this->options['timeout'] = $this->timeout;
        $this->options['connect_timeout'] = $this->connectTimeout;
        $this->options['user_agent'] = $this->userAgent;
        $this->options['verify_ssl'] = $this->verifySSL;

        $this->options = array_merge($this->defaultOptions, $this->options);
        $this->multiCurl = curl_multi_init();
    }

    // ============ MAGIC METHODS ============

    /**
     * Magic setter for backward compatibility
     */
    public function __set($name, $value)
    {
        $map = [
            'verifySSL' => 'verify_ssl',
            'verify_ssl' => 'verify_ssl',
            'connectTimeout' => 'connect_timeout',
            'connect_timeout' => 'connect_timeout',
            'userAgent' => 'user_agent',
            'user_agent' => 'user_agent',
            'timeout' => 'timeout',
        ];

        if (isset($map[$name])) {
            $this->options[$map[$name]] = $value;
            return;
        }

        parent::__set($name, $value);
    }

    /**
     * Magic getter for backward compatibility
     */
    public function __get($name)
    {
        $map = [
            'verifySSL' => 'verify_ssl',
            'verify_ssl' => 'verify_ssl',
            'connectTimeout' => 'connect_timeout',
            'connect_timeout' => 'connect_timeout',
            'userAgent' => 'user_agent',
            'user_agent' => 'user_agent',
        ];

        if (isset($map[$name])) {
            return $this->options[$map[$name]] ?? null;
        }

        // Backward compatibility for response properties
        if ($name === 'response') {
            return $this->_lastResponse['body'] ?? null;
        }
        if ($name === 'httpStatusCode') {
            return $this->_lastResponse['http_code'] ?? null;
        }
        if ($name === 'error') {
            return $this->_lastResponse['error'] ?? null;
        }
        if ($name === 'errorCode') {
            return $this->_lastResponse['error_code'] ?? null;
        }

        return parent::__get($name);
    }

    /**
     * Magic isset for backward compatibility
     */
    public function __isset($name)
    {
        $props = ['response', 'httpStatusCode', 'error', 'errorCode'];
        if (in_array($name, $props)) {
            return isset($this->_lastResponse[$name]);
        }
        return parent::__isset($name);
    }

    // ============ BACKWARD COMPATIBILITY METHODS ============

    /**
     * Set cURL option (backward compatibility with php-curl-class)
     *
     * @param int $option CURLOPT_* constant
     * @param mixed $value Option value
     * @return $this
     */
    public function setOpt($option, $value)
    {
        $this->_curlOptions[$option] = $value;
        return $this;
    }

    /**
     * Get cURL option value
     *
     * @param int $option CURLOPT_* constant
     * @return mixed|null
     */
    public function getOpt($option)
    {
        return $this->_curlOptions[$option] ?? null;
    }

    /**
     * Set multiple cURL options
     *
     * @param array $options Array of CURLOPT_* => value
     * @return $this
     */
    public function setOpts($options)
    {
        foreach ($options as $option => $value) {
            $this->setOpt($option, $value);
        }
        return $this;
    }

    /**
     * Reset client state (backward compatibility)
     *
     * @return $this
     */
    public function reset()
    {
        $this->headers = [];
        $this->cookies = [];
        $this->lastInfo = [];
        $this->middleware = [];
        $this->multiQueue = [];
        $this->_curlOptions = [];
        $this->_lastResponse = [];
        return $this;
    }

    // ============ REQUEST METHODS ============

    /**
     * Send request
     */
    public function request($method, $url, $data = null, $headers = [])
    {
        $this->stats['total_requests']++;
        $startTime = microtime(true);

        // Merge headers
        $allHeaders = array_merge($this->headers, $headers);

        // Prepare request
        $curl = curl_init();

        // Set options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->getOption('timeout'));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->getOption('connect_timeout'));
        curl_setopt($curl, CURLOPT_USERAGENT, $this->getOption('user_agent'));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $this->getOption('follow_location'));
        curl_setopt($curl, CURLOPT_MAXREDIRS, $this->getOption('max_redirects'));
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, [$this, 'parseHeader']);

        // Apply custom curl options (backward compatibility)
        foreach ($this->_curlOptions as $option => $value) {
            curl_setopt($curl, $option, $value);
        }

        // SSL
        if (!$this->getOption('verify_ssl')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Cookies
        if (!empty($this->cookies)) {
            $cookieString = '';
            foreach ($this->cookies as $key => $value) {
                $cookieString .= $key . '=' . urlencode($value) . '; ';
            }
            curl_setopt($curl, CURLOPT_COOKIE, rtrim($cookieString, '; '));
        }

        // Headers
        $headerArray = [];
        foreach ($allHeaders as $key => $value) {
            $headerArray[] = $key . ': ' . $value;
        }
        if (!empty($headerArray)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        }

        // Method
        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                if ($data && is_array($data)) {
                    $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
                    curl_setopt($curl, CURLOPT_URL, $url);
                }
                break;

            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                $this->setBody($curl, $data);
                break;

            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                if ($data !== null) {
                    $this->setBody($curl, $data);
                }
                break;

            case 'HEAD':
                curl_setopt($curl, CURLOPT_NOBODY, true);
                break;

            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                if ($data !== null) {
                    $this->setBody($curl, $data);
                }
        }

        // Execute
        $this->log("Request: {$method} {$url}");

        $body = curl_exec($curl);
        $info = curl_getinfo($curl);
        $error = curl_error($curl);
        $errorCode = curl_errno($curl);

        curl_close($curl);

        // Store for backward compatibility
        $this->_lastResponse = [
            'body' => $body,
            'http_code' => $info['http_code'] ?? 0,
            'error' => $error,
            'error_code' => $errorCode,
        ];
        $this->response = $body;
        $this->httpStatusCode = $info['http_code'] ?? 0;
        $this->error = $error;
        $this->errorCode = $errorCode;

        $this->lastInfo = $info;

        // Update stats
        $executionTime = microtime(true) - $startTime;
        $this->stats['total_time'] += $executionTime;

        if ($error) {
            $this->stats['failed_requests']++;
            $this->log("Request failed: {$error}", 'error');
            throw new \RuntimeException("Request failed: {$error}", $errorCode);
        }

        $this->stats['successful_requests']++;
        $this->log("Response: {$info['http_code']} in " . round($executionTime, 4) . 's');

        // Create response
        $response = new Response($body, $info['http_code'], $info);

        // Process middleware
        foreach ($this->middleware as $middleware) {
            $response = $middleware($response);
        }

        return $response;
    }

    /**
     * Set request body
     */
    protected function setBody($curl, $data)
    {
        if (is_array($data) || is_object($data)) {
            // Check if we should send as JSON
            $isJson = true;
            foreach ($this->headers as $key => $value) {
                if (stripos($key, 'Content-Type') !== false && stripos($value, 'json') === false) {
                    $isJson = false;
                    break;
                }
            }

            if ($isJson) {
                $this->headers['Content-Type'] = 'application/json';
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
    }

    /**
     * Parse header (callback for CURLOPT_HEADERFUNCTION)
     */
    protected function parseHeader($curl, $header)
    {
        $parts = explode(':', $header, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            // Store headers if needed
        }
        return strlen($header);
    }

    /**
     * Get option value
     */
    protected function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $this->defaultOptions[$key];
    }

    // ============ CONVENIENCE METHODS ============

    public function get($url, $params = null, $headers = [])
    {
        return $this->request('GET', $url, $params, $headers);
    }

    public function post($url, $data = null, $headers = [])
    {
        return $this->request('POST', $url, $data, $headers);
    }

    public function put($url, $data = null, $headers = [])
    {
        return $this->request('PUT', $url, $data, $headers);
    }

    public function patch($url, $data = null, $headers = [])
    {
        return $this->request('PATCH', $url, $data, $headers);
    }

    public function delete($url, $data = null, $headers = [])
    {
        return $this->request('DELETE', $url, $data, $headers);
    }

    public function head($url, $headers = [])
    {
        return $this->request('HEAD', $url, null, $headers);
    }

    // ============ CONFIGURATION ============

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function setHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function setCookie($key, $value)
    {
        $this->cookies[$key] = $value;
        return $this;
    }

    public function setCookies($cookies)
    {
        $this->cookies = array_merge($this->cookies, $cookies);
        return $this;
    }

    public function setTimeout($seconds)
    {
        $this->options['timeout'] = $seconds;
        return $this;
    }

    public function setConnectTimeout($seconds)
    {
        $this->options['connect_timeout'] = $seconds;
        return $this;
    }

    public function setUserAgent($userAgent)
    {
        $this->options['user_agent'] = $userAgent;
        return $this;
    }

    public function verifySSL($verify = true)
    {
        $this->options['verify_ssl'] = $verify;
        return $this;
    }

    public function setBearerToken($token)
    {
        $this->headers['Authorization'] = 'Bearer ' . $token;
        return $this;
    }

    public function setBasicAuth($username, $password = '')
    {
        $this->headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
        return $this;
    }

    public function setApiKey($key, $header = 'X-API-Key')
    {
        $this->headers[$header] = $key;
        return $this;
    }

    // ============ FILE DOWNLOAD ============

    public function download($url, $filename, $headers = [])
    {
        $fp = fopen($filename, 'w+');
        if ($fp === false) {
            throw new \RuntimeException("Cannot open file for writing: {$filename}");
        }

        $allHeaders = array_merge($this->headers, $headers);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->getOption('timeout'));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->getOption('verify_ssl'));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->getOption('verify_ssl') ? 2 : 0);

        // Apply custom curl options (backward compatibility)
        foreach ($this->_curlOptions as $option => $value) {
            curl_setopt($curl, $option, $value);
        }

        // Headers
        $headerArray = [];
        foreach ($allHeaders as $key => $value) {
            $headerArray[] = $key . ': ' . $value;
        }
        if (!empty($headerArray)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        }

        $result = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        fclose($fp);

        if ($result === false) {
            throw new \RuntimeException("Download failed: {$error}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("Download failed with status: {$httpCode}");
        }

        return true;
    }

    // ============ MULTI-REQUEST (PARALLEL) ============

    public function addMultiRequest($method, $url, $data = null, $headers = [], $callback = null)
    {
        $this->multiQueue[] = [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'headers' => $headers,
            'callback' => $callback,
        ];
        return $this;
    }

    public function executeMulti()
    {
        $handles = [];
        $responses = [];
        $callbacks = [];

        foreach ($this->multiQueue as $index => $item) {
            $curl = curl_init();
            $url = $item['url'];
            $data = $item['data'];
            $headers = array_merge($this->headers, $item['headers']);

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->getOption('timeout'));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->getOption('verify_ssl'));
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->getOption('verify_ssl') ? 2 : 0);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

            // Apply custom curl options (backward compatibility)
            foreach ($this->_curlOptions as $option => $value) {
                curl_setopt($curl, $option, $value);
            }

            // Headers
            $headerArray = [];
            foreach ($headers as $key => $value) {
                $headerArray[] = $key . ': ' . $value;
            }
            if (!empty($headerArray)) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
            }

            // Method
            $method = strtoupper($item['method']);
            switch ($method) {
                case 'POST':
                    curl_setopt($curl, CURLOPT_POST, true);
                    if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case 'PUT':
                case 'PATCH':
                case 'DELETE':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                    if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                default:
                    if ($data && is_array($data)) {
                        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
                        curl_setopt($curl, CURLOPT_URL, $url);
                    }
            }

            curl_multi_add_handle($this->multiCurl, $curl);
            $handles[$index] = $curl;
            $callbacks[$index] = $item['callback'] ?? null;
        }

        // Execute
        $running = null;
        do {
            curl_multi_exec($this->multiCurl, $running);
            curl_multi_select($this->multiCurl);
        } while ($running > 0);

        // Collect responses
        foreach ($handles as $index => $curl) {
            $body = curl_multi_getcontent($curl);
            $info = curl_getinfo($curl);
            curl_multi_remove_handle($this->multiCurl, $curl);
            curl_close($curl);

            $response = new Response($body, $info['http_code'], $info);

            if ($callbacks[$index] && is_callable($callbacks[$index])) {
                call_user_func($callbacks[$index], $response);
            }

            $responses[$index] = $response;
        }

        $this->multiQueue = [];
        return $responses;
    }

    // ============ MIDDLEWARE ============

    public function addMiddleware($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Middleware must be callable');
        }
        $this->middleware[] = $callback;
        return $this;
    }

    // ============ LOGGING ============

    public function enableLogging($logger = null)
    {
        $this->enableLogging = true;
        $this->logger = $logger;
        return $this;
    }

    protected function log($message, $level = 'info')
    {
        if (!$this->enableLogging) {
            return;
        }

        if ($this->logger && is_callable($this->logger)) {
            call_user_func($this->logger, $message, $level);
        } elseif (class_exists('Hiya\Logging\Logger')) {
            \Hiya\Logging\Logger::log($message, $level);
        } else {
            if (class_exists('Yii')) {
                \Yii::log($message, $level, 'hiya.http.client');
            }
        }
    }

    // ============ STATS ============

    public function getStats()
    {
        return $this->stats;
    }

    public function resetStats()
    {
        $this->stats = [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'total_time' => 0,
        ];
        return $this;
    }

    public function getLastInfo()
    {
        return $this->lastInfo;
    }

    public function getRawResponse()
    {
        return $this->_lastResponse['body'] ?? null;
    }

    public function toArray()
    {
        $response = $this->getRawResponse();
        if (is_string($response)) {
            $decoded = json_decode($response, true);
            return $decoded ?: [];
        }
        return [];
    }

    public function isSuccess()
    {
        return isset($this->_lastResponse['http_code']) &&
               $this->_lastResponse['http_code'] >= 200 &&
               $this->_lastResponse['http_code'] < 300;
    }

    public function isClientError()
    {
        return isset($this->_lastResponse['http_code']) &&
               $this->_lastResponse['http_code'] >= 400 &&
               $this->_lastResponse['http_code'] < 500;
    }

    public function isServerError()
    {
        return isset($this->_lastResponse['http_code']) &&
               $this->_lastResponse['http_code'] >= 500 &&
               $this->_lastResponse['http_code'] < 600;
    }

    public function close()
    {
        if (is_resource($this->multiCurl) || is_object($this->multiCurl)) {
            curl_multi_close($this->multiCurl);
        }
        return $this;
    }

    public function __destruct()
    {
        $this->close();
    }
}