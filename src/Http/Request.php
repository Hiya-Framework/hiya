<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Http
 * @since 1.0
 */

namespace Hiya\Http;

/**
 * HTTP Request - Extends Yii CHttpRequest with modern features
 *
 * Features from Yii CHttpRequest:
 * - getParam(), getPost(), getQuery()
 * - getCookies(), getCookie()
 * - getUserHostAddress(), getUserAgent()
 * - getUrl(), getHostInfo(), getPathInfo()
 * - CSRF validation
 * - Cookie validation
 *
 * Additional Hiya features:
 * - JSON payload parsing
 * - REST parameters (PUT, PATCH, DELETE)
 * - Type casting for parameters
 * - Input sanitization
 * - Custom filtering
 * - Full URL helpers
 * - Modern method names (aliases)
 *
 * Example:
 * ```php
 * $request = new Request();
 *
 * // Modern style (recommended)
 * $username = $request->input('username');
 * if ($request->isPost()) { ... }
 * if ($request->isAjax()) { ... }
 *
 * // Yii style (still works)
 * $username = $request->getParam('username');
 * if ($request->getIsPostRequest()) { ... }
 * ```
 */
class Request extends \CHttpRequest
{
    /**
     * @var array REST parameters (PUT, PATCH, DELETE)
     */
    protected $_restParams;

    /**
     * @var array Parsed JSON data
     */
    protected $_jsonData;

    /**
     * @var string Raw request body
     */
    protected $_rawBody;

    /**
     * Initialize
     */
    public function init()
    {
        parent::init();
        $this->parseRestParams();
    }

    // ============ MODERN METHOD NAMES ============

    /**
     * Get input parameter (GET or POST)
     * Alias for getParam()
     *
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function input($name, $default = null)
    {
        return $this->getParam($name, $default);
    }

    /**
     * Get GET parameter
     * Alias for getQuery()
     *
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function query($name, $default = null)
    {
        return $this->getQuery($name, $default);
    }

    /**
     * Get POST parameter
     * Alias for getPost()
     *
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function post($name, $default = null)
    {
        return $this->getPost($name, $default);
    }

    /**
     * Get all input data
     *
     * @return array
     */
    public function all()
    {
        return array_merge($this->getQueries(), $this->getPosts());
    }

    /**
     * Get all GET parameters
     * Alias for getQueries()
     *
     * @return array
     */
    public function allQueries()
    {
        return $this->getQueries();
    }

    /**
     * Get all POST parameters
     * Alias for getPosts()
     *
     * @return array
     */
    public function allPosts()
    {
        return $this->getPosts();
    }

    /**
     * Get all GET parameters
     * Alias for getQueries()
     *
     * @return array
     */
    public function getQueries()
    {
        return $_GET;
    }

    /**
     * Get all POST parameters
     * Alias for getPosts()
     *
     * @return array
     */
    public function getPosts()
    {
        return $_POST;
    }

    // ============ METHOD CHECKS (MODERN) ============

    /**
     * Check if request is GET
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->getRequestType() === 'GET';
    }

    /**
     * Check if request is POST
     * Alias for getIsPostRequest()
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->getIsPostRequest();
    }

    /**
     * Check if request is PUT
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->getRequestType() === 'PUT';
    }

    /**
     * Check if request is PATCH
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->getRequestType() === 'PATCH';
    }

    /**
     * Check if request is DELETE
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->getRequestType() === 'DELETE';
    }

    /**
     * Check if request is HEAD
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->getRequestType() === 'HEAD';
    }

    /**
     * Check if request is OPTIONS
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->getRequestType() === 'OPTIONS';
    }

    /**
     * Check if request is AJAX
     * Alias for getIsAjaxRequest()
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->getIsAjaxRequest();
    }

    /**
     * Check if request is secure (HTTPS)
     * Alias for getIsSecureConnection()
     *
     * @return bool
     */
    public function isSecure()
    {
        return $this->getIsSecureConnection();
    }

    /**
     * Check if request is JSON
     *
     * @return bool
     */
    public function isJson()
    {
        $contentType = $this->getContentType();
        return $contentType && strpos($contentType, 'application/json') !== false;
    }

    /**
     * Check if request has file
     *
     * @param string $name File input name
     * @return bool
     */
    public function hasFile($name)
    {
        return isset($_FILES[$name]) && $_FILES[$name]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Check if parameter exists
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->hasParam($name);
    }

    /**
     * Check if parameter exists
     * Alias for has()
     *
     * @param string $name
     * @return bool
     */
    public function hasParam($name)
    {
        return isset($_GET[$name]) || isset($_POST[$name]);
    }

    /**
     * Check if POST parameter exists
     *
     * @param string $name
     * @return bool
     */
    public function hasPost($name)
    {
        return isset($_POST[$name]);
    }

    /**
     * Check if GET parameter exists
     *
     * @param string $name
     * @return bool
     */
    public function hasQuery($name)
    {
        return isset($_GET[$name]);
    }

    // ============ JSON PAYLOAD ============

    /**
     * Get JSON payload
     *
     * @param bool $assoc Return as associative array
     * @return mixed
     */
    public function getJson($assoc = true)
    {
        if ($this->_jsonData === null) {
            $body = $this->getRawBody();
            if (!empty($body)) {
                $this->_jsonData = json_decode($body, $assoc);
            }
        }
        return $this->_jsonData;
    }

    /**
     * Get JSON value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function json($key = null, $default = null)
    {
        if ($key === null) {
            return $this->getJson();
        }
        $data = $this->getJson();
        return isset($data[$key]) ? $data[$key] : $default;
    }

    /**
     * Check if request has JSON payload
     *
     * @return bool
     */
    public function hasJson()
    {
        return $this->getJson() !== null;
    }

    // ============ REST PARAMETERS ============

    /**
     * Get REST parameter (PUT, PATCH, DELETE)
     *
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function getRestParam($name, $default = null)
    {
        $this->parseRestParams();
        return isset($this->_restParams[$name]) ? $this->_restParams[$name] : $default;
    }

    /**
     * Get all REST parameters
     *
     * @return array
     */
    public function getRestParams()
    {
        $this->parseRestParams();
        return $this->_restParams;
    }

    /**
     * Parse REST parameters from raw body
     */
    protected function parseRestParams()
    {
        if ($this->_restParams !== null) {
            return;
        }

        $method = $this->getRequestType();

        // Only parse for PUT, PATCH, DELETE
        if (!in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
            $this->_restParams = [];
            return;
        }

        // Check if POST has _method (method override)
        if (isset($_POST['_method'])) {
            $this->_restParams = $_POST;
            return;
        }

        $body = $this->getRawBody();

        // Try JSON
        if ($this->isJson()) {
            $data = json_decode($body, true);
            if ($data !== null) {
                $this->_restParams = $data;
                return;
            }
        }

        // Try query string
        parse_str($body, $data);
        $this->_restParams = $data;
    }

    // ============ RAW BODY ============

    /**
     * Get raw request body
     * Override parent to cache
     *
     * @return string
     */
    public function getRawBody()
    {
        if ($this->_rawBody === null) {
            $this->_rawBody = parent::getRawBody();
        }
        return $this->_rawBody;
    }

    // ============ CONTENT TYPE ============

    /**
     * Get content type
     *
     * @return string|null
     */
    public function getContentType()
    {
        return parent::getContentType();
    }

    // ============ TYPE CASTING ============

    /**
     * Get parameter with type casting
     *
     * @param string $name Parameter name
     * @param string $type Type to cast to (string, int, float, bool, array)
     * @param mixed $default Default value
     * @return mixed
     */
    public function inputAs($name, $type = 'string', $default = null)
    {
        $value = $this->getParam($name, $default);
        return $this->cast($value, $type);
    }

    /**
     * Get POST parameter with type casting
     *
     * @param string $name Parameter name
     * @param string $type Type to cast to
     * @param mixed $default Default value
     * @return mixed
     */
    public function postAs($name, $type = 'string', $default = null)
    {
        $value = $this->getPost($name, $default);
        return $this->cast($value, $type);
    }

    /**
     * Get query parameter with type casting
     *
     * @param string $name Parameter name
     * @param string $type Type to cast to
     * @param mixed $default Default value
     * @return mixed
     */
    public function queryAs($name, $type = 'string', $default = null)
    {
        $value = $this->getQuery($name, $default);
        return $this->cast($value, $type);
    }

    /**
     * Cast value to type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected function cast($value, $type)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
                return (array) $value;
            case 'string':
            default:
                return (string) $value;
        }
    }

    // ============ SANITIZATION ============

    /**
     * Sanitize value (strip tags, trim)
     *
     * @param string $value
     * @return string
     */
    public function sanitize($value)
    {
        return trim(strip_tags((string) $value));
    }

    /**
     * Sanitize parameter
     *
     * @param string $name Parameter name
     * @param string $default Default value
     * @return string
     */
    public function sanitizeInput($name, $default = '')
    {
        return $this->sanitize($this->getParam($name, $default));
    }

    /**
     * Sanitize POST parameter
     *
     * @param string $name Parameter name
     * @param string $default Default value
     * @return string
     */
    public function sanitizePost($name, $default = '')
    {
        return $this->sanitize($this->getPost($name, $default));
    }

    /**
     * Sanitize query parameter
     *
     * @param string $name Parameter name
     * @param string $default Default value
     * @return string
     */
    public function sanitizeQuery($name, $default = '')
    {
        return $this->sanitize($this->getQuery($name, $default));
    }

    // ============ CUSTOM FILTERING ============

    /**
     * Filter input with custom callback
     *
     * @param string $name Parameter name
     * @param callable $filter Filter function
     * @param mixed $default Default value
     * @return mixed
     */
    public function filter($name, $filter, $default = null)
    {
        $value = $this->getParam($name, $default);
        return $filter($value);
    }

    /**
     * Filter POST input
     *
     * @param string $name Parameter name
     * @param callable $filter Filter function
     * @param mixed $default Default value
     * @return mixed
     */
    public function filterPost($name, $filter, $default = null)
    {
        $value = $this->getPost($name, $default);
        return $filter($value);
    }

    /**
     * Filter query input
     *
     * @param string $name Parameter name
     * @param callable $filter Filter function
     * @param mixed $default Default value
     * @return mixed
     */
    public function filterQuery($name, $filter, $default = null)
    {
        $value = $this->getQuery($name, $default);
        return $filter($value);
    }

    // ============ URL HELPERS ============

    /**
     * Get full URL
     *
     * @return string
     */
    public function getFullUrl()
    {
        $scheme = $this->getIsSecureConnection() ? 'https' : 'http';
        $host = $this->getHostInfo();
        $uri = $this->getRequestUri();
        return $scheme . '://' . $host . $uri;
    }

    /**
     * Get base path (without script name)
     *
     * @return string
     */
    public function getBasePath()
    {
        $scriptUrl = $this->getScriptUrl();
        $basePath = rtrim(dirname($scriptUrl), '\\/');
        return $basePath === '.' ? '' : $basePath;
    }

    /**
     * Get client IP
     * Alias for getUserHostAddress()
     *
     * @return string
     */
    public function getClientIp()
    {
        return $this->getUserHostAddress();
    }

    // ============ COOKIES ============

    /**
     * Get cookie
     * Alias for getCookie()
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function cookie($name, $default = null)
    {
        return $this->getCookie($name, $default);
    }

    /**
     * Get all cookies
     *
     * @return array
     */
    public function allCookies()
    {
        return $this->getCookies()->toArray();
    }

    // ============ HEADERS ============

    /**
     * Get all headers
     *
     * @return array
     */
    public function allHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        return $headers;
    }

    /**
     * Get specific header
     *
     * @param string $name
     * @param string $default
     * @return string|null
     */
    public function header($name, $default = null)
    {
        $headers = $this->allHeaders();
        $name = ucwords(strtolower($name), '-');
        return isset($headers[$name]) ? $headers[$name] : $default;
    }
}