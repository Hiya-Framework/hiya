<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Base
 * @since 1.0
 * 
 * Web Controller
 */

namespace Hiya\Base;

use Hiya\Http\Response;
use Hiya\Http\Request;

class Controller extends \CController
{
    /**
     * @var string default layout
     */
    public $layout = 'main';

    /**
     * @var Response HTTP Response instance
     */
    protected $response;

    /**
     * @var Request HTTP Request instance
     */
    protected $request;

    /**
     * @var bool Use Response system for render
     */
    public $useResponseForRender = false;

    /**
     * @var bool Is this Api Controller
     */
    public $isApi = false;

    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();
        $this->response = Response::create();
        $this->request = new Request();
        $this->request->init();
    }

    protected function isApiRequest()
    {
        return $this->isApi;
    }   

    /**
     * Create URL
     *
     * @param string $route
     * @param array $params
     * @param string $ampersand
     * @return string
     */
    public function url($route, $params = array(), $ampersand = '&')
    {
        return \Hiya::app()->createUrl($route, $params, $ampersand);
    }

    /**
     * Set flash message
     * @param string $type (success, error, warning, info)
     * @param string $message
     */
    public function setFlash($type, $message)
    {
        \Hiya::app()->user->setFlash($type, $message);
    }

    /**
     * Get flash message
     * @param string $type
     * @return mixed
     */
    public function getFlash($type)
    {
        return \Hiya::app()->user->getFlash($type);
    }

    /**
     * Get current user ID
     * @return int|null
     */
    public function getUserId()
    {
        return \Hiya::app()->user->isGuest ? null : \Hiya::app()->user->id;
    }

    /**
     * Get current user data
     * @return mixed
     */
    public function getUser()
    {
        return \Hiya::app()->user->isGuest ? null : \Hiya::app()->user->getUserData();
    }

    /**
     * Check if request is AJAX
     * @return bool
     */
    protected function isAjax()
    {
        return $this->request->isAjax();
    }

}