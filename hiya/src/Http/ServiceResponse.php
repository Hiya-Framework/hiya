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
 * Service Response - For service results
 */
class ServiceResponse extends AbstractResponse
{
    /**
     * @var mixed Service result
     */
    protected $result;
    
    /**
     * @var string Service name
     */
    protected $serviceName;
    
    /**
     * @var array Metrics
     */
    protected $metrics = [];
    
    /**
     * @var bool Success status
     */
    protected $success = true;
    
    /**
     * @var string Error message
     */
    protected $error;
    
    /**
     * Constructor
     */
    public function __construct($result = null, $serviceName = null)
    {
        $this->result = $result;
        $this->serviceName = $serviceName;
        $this->withHeader('Content-Type', 'application/json');
        $this->buildBody();
    }
    
    /**
     * Build JSON body
     */
    protected function buildBody()
    {
        $response = [
            'success' => $this->success,
        ];
        
        if ($this->success) {
            $response['data'] = $this->result;
        } else {
            $response['error'] = $this->error;
        }
        
        if ($this->serviceName) {
            $response['service'] = $this->serviceName;
        }
        
        if (!empty($this->metrics)) {
            $response['metrics'] = $this->metrics;
        }
        
        $this->body = json_encode($response);
    }
    
    /**
     * Set result
     */
    public function result($result)
    {
        $this->result = $result;
        $this->success = true;
        $this->buildBody();
        return $this;
    }
    
    /**
     * Set error
     */
    public function error($message, $code = 500)
    {
        $this->error = $message;
        $this->success = false;
        $this->statusCode = $code;
        $this->buildBody();
        return $this;
    }
    
    /**
     * Set metrics
     */
    public function metrics($metrics)
    {
        $this->metrics = $metrics;
        $this->buildBody();
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function sendBody()
    {
        echo $this->body;
    }
    
    /**
     * Static factory
     */
    public static function success($result, $serviceName = null)
    {
        return new static($result, $serviceName);
    }
    
    public static function fail($message, $code = 500, $serviceName = null)
    {
        $response = new static(null, $serviceName);
        return $response->error($message, $code);
    }
}