<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Base\Controller
 * @since 1.0
 * 
 * Api Controller
 */

namespace Hiya\Base;

use Hiya;
use Hiya\Http\Response;
use Hiya\Http\Request;
use Override;

class ApiController extends Controller
{
    protected $request;
    protected $response;
    protected $allowedMethods = [];

    // this is api base controller
    public $isApi = true;

    // this is default header
    protected $defaultHeaders = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Content-Security-Policy' => "default-src 'self'",
    ];

    protected $user = null; // this for user data

    protected $publicActions = ['login'];

    public function init()
    {
        parent::init();
        $this->layout = false;

        $this->request = \Hiya::app()->request;
        $this->request->enableCsrfValidation = false;
        $this->request->init();

        $this->response = new Response();
    }

    public function getIsApi()
    {
        return $this->isApi;
    }
    

    #[Override]
    protected function beforeAction($action)
    {
        $actionId = $action->id;

        if (isset($this->allowedMethods[$actionId])) {
            $allowed = (array) $this->allowedMethods[$actionId];
            $current = $this->request->getRequestType(); 

            if (!in_array($current, $allowed)) {
                if (!in_array($current, $allowed)) {
                    throw new \CHttpException(404, 'The requested page does not exist.');
                }
            }
        }

        $this->applyHeaders();

        if(!in_array($action->id, $this->publicActions)){
            $this->user = \Hiya::app()->getComponent('apiAuth')->authorize();
        }

        return parent::beforeAction($action);
    }

    protected function applyHeaders()
    {
        $headers = array_merge($this->defaultHeaders, $this->getCustomHeaders());

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
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

        /*
        if (defined('YII_DEBUG') && YII_DEBUG) {
            $response['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }
        */

        if (ob_get_length()) ob_end_clean();

        $this->response->withStatus($status)
                    ->json($response)
                    ->send();
                    
        Hiya::app()->end();
    }
    
    /**
    * success response
    */
    protected function success($data = [], $message = 'Success', $status = 200)
    {
        return $this->response->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ])->withStatus($status)->send();
    }

    /**
     * error response
     */
    protected function error($message = 'Error', $status = 400)
    {
        return $this->response->json([
            'success' => false,
            'message' => $message,
        ])->withStatus($status)->send();
    }
}