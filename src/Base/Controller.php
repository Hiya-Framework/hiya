<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Controller
 * @since 1.0
 */
namespace Hiya;

use CJSON;

class Controller extends \CController
{
    /**
     * @var string default layout
     */
    public $layout = 'main';
    
    /**
     * @var array breadcrumbs
     */
    public $breadcrumbs = array();
    
    /**
     * @var array menu items
     */
    public $menu = array();
    
    /**
     * @var string page title
     */
    public $pageTitle = '';
    
    /**
     * @var string page title suffix
     */
    public $pageTitleSuffix = ' - Hiya Framework';
    
    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();
        // Page title will be set in beforeAction
    }
    
    /**
     * Before action - runs before every action
     * @param \CAction $action
     * @return bool
     */
    protected function beforeAction($action)
    {
        // Set default page title
        if (empty($this->pageTitle)) {
            $controllerId = $this->getId();
            $actionId = $action->getId();
            $this->pageTitle = ucfirst($controllerId) . ' ' . ucfirst($actionId) . $this->pageTitleSuffix;
        }
        
        // Check if user is logged in for protected actions
        if ($this->isProtectedAction($action) && \Hiya::app()->user->isGuest) {
            \Hiya::app()->user->loginRequired();
            return false;
        }
        
        // Set CSRF token for forms
        if (\Hiya::app()->request->isPostRequest) {
            \Hiya::app()->request->enableCsrfValidation = true;
        }
        
        return parent::beforeAction($action);
    }
    
    /**
     * After action - runs after every action
     * @param \CAction $action
     * @return bool
     */
    protected function afterAction($action)
    {
        // Log action execution
        if (defined('YII_DEBUG') && YII_DEBUG) {
            \Hiya::log("Action: " . $this->getId() . '/' . $action->getId(), 'trace', 'controller');
        }
        
        return parent::afterAction($action);
    }
    
    /**
     * Check if action requires authentication
     * @param \CAction $action
     * @return bool
     */
    protected function isProtectedAction($action)
    {
        $protectedActions = $this->protectedActions();
        return in_array($action->getId(), $protectedActions);
    }
    
    /**
     * Define actions that require authentication
     * Override in child controller
     * @return array
     */
    protected function protectedActions()
    {
        return array();
    }
    
    /**
     * Render JSON response with logging
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @param bool $log Log the response (default: false)
     */
    public function renderJson($data, $statusCode = 200, $log = false)
    {
        \Hiya::app()->response->statusCode = $statusCode;
        \Hiya::app()->response->contentType = 'application/json';
        $this->layout = false;
        
        // Gunakan json_encode bawaan PHP
        $json = json_encode($data);
        
        if ($log && defined('YII_DEBUG') && YII_DEBUG) {
            \Hiya::log("JSON Response: " . substr($json, 0, 500), 'info', 'controller');
        }
        
        echo $json;
        \Hiya::app()->end();
    }
    
    /**
     * Render error as JSON
     * @param string $message
     * @param int $statusCode
     */
    public function renderErrorJson($message, $statusCode = 400)
    {
        $this->renderJson([
            'error' => true,
            'message' => $message,
            'code' => $statusCode,
        ], $statusCode);
    }
    
    /**
     * Render success as JSON
     * @param mixed $data
     * @param string $message
     */
    public function renderSuccessJson($data = null, $message = 'Success')
    {
        $this->renderJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }
    
    /**
     * Redirect back to previous page
     * @param string $defaultUrl
     */
    public function redirectBack($defaultUrl = array('site/index'))
    {
        $backUrl = \Hiya::app()->request->urlReferrer;
        if ($backUrl) {
            $this->redirect($backUrl);
        } else {
            $this->redirect($defaultUrl);
        }
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
     * Check if user has permission
     * @param string $permission
     * @return bool
     */
    public function checkPermission($permission)
    {
        if (\Hiya::app()->user->isGuest) {
            return false;
        }
        
        // Use Gate component if available
        if (\Hiya::app()->hasComponent('auth')) {
            return \Hiya::app()->auth->can($permission);
        }
        
        // Fallback to simple role check
        $userRole = \Hiya::app()->user->getState('role', 'guest');
        return in_array($userRole, ['admin', 'superadmin']);
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
     * Add breadcrumb
     * @param string $label
     * @param string $url
     */
    public function addBreadcrumb($label, $url = null)
    {
        $this->breadcrumbs[] = array($label, $url);
    }
    
    /**
     * Set page title
     * @param string $title
     */
    public function setPageTitle($title)
    {
        $this->pageTitle = $title . $this->pageTitleSuffix;
    }
    
    /**
     * Get pagination helper
     * @param int $totalItems
     * @param int $pageSize
     * @return \CPagination
     */
    protected function getPagination($totalItems, $pageSize = 20)
    {
        $pagination = new \CPagination($totalItems);
        $pagination->pageSize = $pageSize;
        return $pagination;
    }
    
    /**
     * Check if request is AJAX
     * @return bool
     */
    protected function isAjax()
    {
        return \Hiya::app()->request->isAjaxRequest;
    }
    
    /**
     * Get request parameter with default
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getParam($name, $default = null)
    {
        return \Hiya::app()->request->getParam($name, $default);
    }
}