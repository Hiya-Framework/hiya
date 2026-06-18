<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Base
 * @since 1.0
 * 
 * Api Controller
 */

namespace Hiya\Base;

use Hiya;
use Hiya\Base\Traits\ApiResponse;
use Hiya\Http\Response;
use Override;

class ApiController extends Controller
{
    use ApiResponse;

    protected $request;
    protected $response;
    protected $allowedMethods = [];

    // this is api base controller
    public $isApi = true;

    // allowed ips
    protected $allowedIps = []; // '127.0.0.1', '::1'

    protected $rateLimit = [
        'enabled' => false,
        'limit' => 60,      // Requests per minute
        'decay' => 60,      // Decay in seconds
    ];
    
    protected $apiVersion = 'v1';
    protected $responseFormat = 'json';


    // this is default header
    protected $defaultHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
        ];

    protected $user = null; // this for user data

    protected $publicActions = ['login'];

    // CORS Configuration
    protected $cors = [
        'enabled' => true,
        'origin' => '*',
        'methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'headers' => 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token',
        'credentials' => true,
        'max_age' => 86400,
    ];

    public function init()
    {
        parent::init();
        $this->layout = false;

        $this->request = \Hiya::app()->request;
        $this->request->enableCsrfValidation = false;
        $this->request->init();

        // Set default headers
        $this->applyHeaders();

        $this->response = new Response();

        // Handle CORS
        if ($this->cors['enabled']) {
            $this->handleCors();
        }
    }

    public function getIsApi()
    {
        return $this->isApi;
    }
    
    /**
     * Handle CORS preflight and headers
     */
    protected function handleCors()
    {
        // Set CORS headers
        $this->setCorsHeaders();
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Set CORS headers
     */
    protected function setCorsHeaders()
    {
        $origin = $this->cors['origin'] === '*' ? '*' : $this->cors['origin'];
        
        $this->response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', $this->cors['methods'])
            ->withHeader('Access-Control-Allow-Headers', $this->cors['headers']);
        
        if ($this->cors['credentials']) {
            $this->response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        if ($this->cors['max_age']) {
            $this->response->withHeader('Access-Control-Max-Age', (string)$this->cors['max_age']);
        }
    }


    #[Override]
    protected function beforeAction($action)
    {
        // Check IP if allowedIps is not empty
        if (!empty($this->allowedIps)) {
            $this->checkIpAddress();
        }

        // Check allowed methods
        $this->checkAllowedMethods($action->id);

        // Rate limiting
        if ($this->rateLimit['enabled']) {
            $this->checkRateLimit();
        }

        $this->applyHeaders();

        if(!in_array($action->id, $this->publicActions)){
            $this->user = \Hiya::app()->getComponent('apiAuth')->authorize();
        }

        return parent::beforeAction($action);
    }

    /**
     * Check allowed methods
     */
    protected function checkAllowedMethods($actionId)
    {
        if (isset($this->allowedMethods[$actionId])) {
            $allowed = (array) $this->allowedMethods[$actionId];
            $current = $this->request->getRequestType();
            
            if (!in_array($current, $allowed)) {
                throw new \CHttpException(405, 'Method not allowed');
            }
        }
    }

    protected function checkIpAddress()
    {
        $clientIp = \Hiya::app()->request->getUserHostAddress();

        if (!in_array($clientIp, $this->allowedIps)) {
            throw new \CHttpException(403, 'Access Denied');
        }
    }

    protected function applyHeaders()
    {
        $headers = array_merge($this->defaultHeaders, $this->getCustomHeaders());

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
    }

    /**
     * Check rate limit
     */
    protected function checkRateLimit()
    {
        $key = $this->getRateLimitKey();
        $cache = $this->getCache();
        
        $current = $cache->get($key) ?: 0;
        
        if ($current >= $this->rateLimit['limit']) {
            throw new \CHttpException(429, 'Too many requests. Please try again later.');
        }
        
        $cache->set($key, $current + 1, $this->rateLimit['decay']);
    }
    
    /**
     * Get rate limit key
     */
    protected function getRateLimitKey()
    {
        $ip = \Hiya::app()->request->getUserHostAddress();
        $action = \Hiya::app()->controller->action->id;
        return "rate_limit:{$ip}:{$action}";
    }
    
    /**
     * Get cache component
     */
    protected function getCache()
    {
        return \Hiya::app()->getComponent('cache') ?: new \CFileCache();
    }

    protected function getCustomHeaders()
    {
        return [];
    }

    public function runAction($action)
    {
        try {
            return parent::runAction($action);
        } catch (\CHttpException $e) {
            // RE-THROW the 404/403/etc so the global ApiErrorHandler catches it
            throw $e;
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * format all excaption to be json reponse
     * @param \Exception $e
     */
    public function handleException(\Exception $e)
    {
        $status = ($e instanceof \CHttpException) ? $e->statusCode : 500;
        
        $response = [
            'success' => false,
            'message' => $e->getMessage(),
            'code'    => $status,
        ];

        // Add validation errors if available
        if (method_exists($e, 'getErrors')) {
            $response['errors'] = $e->getErrors();
        }
        
        // Debug info in development
        if (defined('YII_DEBUG') && YII_DEBUG) {
            $response['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => [
                    'url' => $this->request->getRequestUri(),
                    'method' => $this->request->getRequestType(),
                    'ip' => $this->request->getUserHostAddress(),
                ],
            ];
        }        

        if (ob_get_length()) ob_end_clean();

        $this->response->withStatus($status)
                    ->json($response)
                    ->send();
                    
        Hiya::app()->end();
    }
    

    /**
     * Get request data (JSON or form)
     */
    protected function getRequestData($key = null, $default = null)
    {
        $raw = $this->request->getRawBody();
        $data = json_decode($raw, true);
        
        if ($data === null) {
            $data = $_POST;
        }
        
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : $default;
        }
        
        return $data;
    }
    
}