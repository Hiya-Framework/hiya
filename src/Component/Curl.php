<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Component\Curl
 * @since 1.0
 */

namespace Hiya\Component;

// Load Composer autoload if available
$composerAutoload = HIYA_PATH . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    // Manual load Curl library
    $curlBasePath = HIYA_PATH . '/libs/php-curl-class/src/Curl/';
    if (is_dir($curlBasePath)) {
        foreach (glob($curlBasePath . '*.php') as $file) {
            require_once $file;
        }
    }
}

class Curl extends \CApplicationComponent
{
    public $defaultOptions = [];
    protected $_curl;
    protected $_multiCurl;
    public $timeout = 30;
    public $connectTimeout = 10;
    public $userAgent = 'Hiya Framework';
    public $verifySSL = false;
    
    public function init()
    {
        parent::init();
        
        // Pastikan class Curl tersedia
        if (!class_exists('\Curl\Curl')) {
            throw new \CException('Curl library not loaded. Please install php-curl-class via composer or manual download.');
        }
        
        $this->_curl = new \Curl\Curl();
        $this->_multiCurl = new \Curl\MultiCurl();
        
        $this->_curl->setTimeout($this->timeout);
        $this->_curl->setConnectTimeout($this->connectTimeout);
        $this->_curl->setUserAgent($this->userAgent);
        
        if (!$this->verifySSL) {
            $this->_curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $this->_curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        }
        
        foreach ($this->defaultOptions as $option => $value) {
            $this->_curl->setOpt($option, $value);
        }
    }
    
    public function __call($name, $arguments)
    {
        if (method_exists($this->_curl, $name)) {
            return call_user_func_array([$this->_curl, $name], $arguments);
        }
        throw new \CException("Method '{$name}' not found");
    }
    
    public function __get($name)
    {
        $properties = ['error', 'errorMessage', 'errorCode', 'response', 'responseHeaders', 
                       'requestHeaders', 'httpStatusCode', 'info', 'url'];
        
        if (in_array($name, $properties)) {
            return $this->_curl->$name;
        }
        
        return parent::__get($name);
    }
    
    public function __isset($name)
    {
        $properties = ['error', 'errorMessage', 'errorCode', 'response', 'responseHeaders', 
                       'requestHeaders', 'httpStatusCode', 'info', 'url'];
        
        if (in_array($name, $properties)) {
            return isset($this->_curl->$name);
        }
        
        return parent::__isset($name);
    }
    
    public function get($url, $data = [])
    {
        return $this->_curl->get($url, $data);
    }
    
    public function post($url, $data = '')
    {
        return $this->_curl->post($url, $data);
    }
    
    public function put($url, $data = [])
    {
        return $this->_curl->put($url, $data);
    }
    
    public function delete($url, $data = [])
    {
        return $this->_curl->delete($url, $data);
    }
    
    public function patch($url, $data = [])
    {
        return $this->_curl->patch($url, $data);
    }
    
    public function setHeader($key, $value)
    {
        $this->_curl->setHeader($key, $value);
        return $this;
    }
    
    public function setTimeout($seconds)
    {
        $this->_curl->setTimeout($seconds);
        return $this;
    }
    
    public function setOpt($option, $value)
    {
        $this->_curl->setOpt($option, $value);
        return $this;
    }
    
    public function setBearerToken($token)
    {
        $this->_curl->setHeader('Authorization', 'Bearer ' . $token);
        return $this;
    }
    
    public function setBasicAuth($username, $password = '')
    {
        $this->_curl->setBasicAuthentication($username, $password);
        return $this;
    }
    
    public function download($url, $filename)
    {
        return $this->_curl->download($url, $filename);
    }
    
    public function getRawResponse()
    {
        return $this->_curl->response;
    }
    
    public function toArray()
    {
        if (is_array($this->_curl->response)) {
            return $this->_curl->response;
        }
        if (is_object($this->_curl->response)) {
            return (array) $this->_curl->response;
        }
        if (is_string($this->_curl->response)) {
            $decoded = json_decode($this->_curl->response, true);
            return $decoded ?: [];
        }
        return [];
    }
    
    public function isSuccess()
    {
        return !$this->_curl->error && $this->_curl->httpStatusCode >= 200 && $this->_curl->httpStatusCode < 300;
    }
    
    public function isClientError()
    {
        return $this->_curl->httpStatusCode >= 400 && $this->_curl->httpStatusCode < 500;
    }
    
    public function isServerError()
    {
        return $this->_curl->httpStatusCode >= 500 && $this->_curl->httpStatusCode < 600;
    }
    
    public function reset()
    {
        $this->_curl->reset();
        return $this;
    }
    
    public function close()
    {
        $this->_curl->close();
    }
    
    public function getCurl()
    {
        return $this->_curl;
    }
    
    public function getMultiCurl()
    {
        return $this->_multiCurl;
    }
}