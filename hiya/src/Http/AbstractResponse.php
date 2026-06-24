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
 * Abstract Response - Base class for all responses
 */
abstract class AbstractResponse implements ResponseInterface
{
    /**
     * @var int HTTP status code
     */
    protected $statusCode = 200;
    
    /**
     * @var array Headers
     */
    protected $headers = [];
    
    /**
     * @var mixed Response body
     */
    protected $body;
    
    /**
     * @var bool Terminate after send
     */
    protected $terminate = true;
    
    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withStatus($code)
    {
        $this->statusCode = (int) $code;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withBody($body)
    {
        $this->body = $body;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Send headers
     */
    protected function sendHeaders()
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendBody();
        
        if ($this->terminate && class_exists('Yii')) {
            \Yii::app()->end();
        }
    }
    
    /**
     * Send body (implemented by child classes)
     */
    abstract protected function sendBody();
    
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) $this->getBody();
    }
}