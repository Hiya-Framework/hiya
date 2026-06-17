<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Base\Controller
 * @since 1.0
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
     * Initialize controller
     */
    public function init()
    {
        parent::init();
        $this->response = Response::create();
        $this->request = new Request();
        $this->request->init();
    }

    /**
     * Get response instance
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get request instance
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
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
        if ($this->request->isPost()) {
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

    // ============ RENDER METHODS (UNCHANGED) ============

    /**
     * Renders a view with layout.
     * KEPT UNCHANGED - Same as Yii base
     *
     * @param string $view name of the view to be rendered
     * @param array $data data to be extracted
     * @param bool $return whether the rendering result should be returned
     * @return string|void
     */
    public function render($view, $data = null, $return = false)
    {
        return parent::render($view, $data, $return);
    }

    /**
     * Renders a view without layout.
     * KEPT UNCHANGED - Same as Yii base
     *
     * @param string $view name of the view to be rendered
     * @param array $data data to be extracted
     * @param bool $return whether the rendering result should be returned
     * @param bool $processOutput whether to process output
     * @return string|void
     */
    public function renderPartial($view, $data = null, $return = false, $processOutput = false)
    {
        return parent::renderPartial($view, $data, $return, $processOutput);
    }

    /**
     * Redirect to URL.
     * KEPT UNCHANGED - Same as Yii base
     *
     * @param string $url URL to redirect to
     * @param bool $terminate whether to terminate the application
     * @param int $statusCode HTTP status code
     */
    public function redirect($url, $terminate = true, $statusCode = 302)
    {
        parent::redirect($url, $terminate, $statusCode);
    }

    // ============ RESPONSE METHODS ============

    /**
     * Render JSON response
     *
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @param bool $log Log the response
     * @return Response
     */
    public function jsonResponse($data, $statusCode = 200, $log = false)
    {
        $this->layout = false;

        if ($log && defined('YII_DEBUG') && YII_DEBUG) {
            \Hiya::log("JSON Response: " . substr(json_encode($data), 0, 500), 'info', 'controller');
        }

        return $this->response
            ->withStatus($statusCode)
            ->json($data);
    }

    /**
     * Render error as JSON
     *
     * @param string $message
     * @param int $statusCode
     * @param int $errorCode
     * @return Response
     */
    public function jsonError($message, $statusCode = 400, $errorCode = null)
    {
        $this->layout = false;
        $data = ['error' => $message, 'code' => $statusCode];

        if ($errorCode !== null) {
            $data['error_code'] = $errorCode;
        }

        return $this->response
            ->withStatus($statusCode)
            ->json($data);
    }

    /**
     * Render success as JSON
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return Response
     */
    public function jsonSuccess($data = null, $message = 'Success', $statusCode = 200)
    {
        $this->layout = false;
        $response = ['success' => true, 'message' => $message];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->response
            ->withStatus($statusCode)
            ->json($response);
    }

    /**
     * Render service response (for external service results)
     *
     * @param mixed $result Service result
     * @param string|null $serviceName Service name
     * @param int $statusCode
     * @return Response
     */
    public function serviceResponse($result, $serviceName = null, $statusCode = 200)
    {
        $this->layout = false;
        return $this->response
            ->withStatus($statusCode)
            ->service($result, $serviceName);
    }

    /**
     * Render service error response
     *
     * @param string $message Error message
     * @param int $statusCode
     * @param string|null $serviceName
     * @return Response
     */
    public function serviceErrorResponse($message, $statusCode = 500, $serviceName = null)
    {
        $this->layout = false;
        return $this->response
            ->withStatus($statusCode)
            ->serviceError($message, $statusCode, $serviceName);
    }

    /**
     * Render HTML view with layout and return as Response
     *
     * @param string $view View name
     * @param array $data View data
     * @param int $statusCode HTTP status code
     * @return Response
     */
    public function view($view, $data = [], $statusCode = 200)
    {
        $content = parent::render($view, $data, true);
        return $this->response
            ->withStatus($statusCode)
            ->html($content);
    }

    /**
     * Render HTML view without layout and return as Response
     *
     * @param string $view View name
     * @param array $data View data
     * @param int $statusCode HTTP status code
     * @return Response
     */
    public function viewPartial($view, $data = [], $statusCode = 200)
    {
        $this->layout = false;
        $content = parent::renderPartial($view, $data, true);
        return $this->response
            ->withStatus($statusCode)
            ->html($content);
    }

    /**
     * Redirect with Response
     *
     * @param string $url
     * @param int $statusCode
     * @return Response
     */
    public function redirectResponse($url, $statusCode = 302)
    {
        return $this->response->redirect($url, $statusCode);
    }

    /**
     * Download file with Response
     *
     * @param string $filePath
     * @param string|null $fileName
     * @param string|null $mimeType
     * @return Response
     */
    public function downloadResponse($filePath, $fileName = null, $mimeType = null)
    {
        return $this->response->download($filePath, $fileName, $mimeType);
    }

    /**
     * Send file inline with Response
     *
     * @param string $filePath
     * @param string|null $fileName
     * @param string|null $mimeType
     * @return Response
     */
    public function fileResponse($filePath, $fileName = null, $mimeType = null)
    {
        return $this->response->file($filePath, $fileName, $mimeType);
    }

    /**
     * Stream response
     *
     * @param callable $callback
     * @param string $contentType
     * @return Response
     */
    public function streamResponse($callback, $contentType = 'text/plain')
    {
        $this->layout = false;
        $this->response->withHeader('Content-Type', $contentType);
        $this->response->withBody($callback);
        return $this->response;
    }

    /**
     * Stream JSON Lines (NDJSON)
     *
     * @param callable $generator Generator function
     * @return Response
     */
    public function streamJsonLines($generator)
    {
        $this->layout = false;

        $callback = function() use ($generator) {
            foreach ($generator as $item) {
                echo json_encode($item) . "\n";
                ob_flush();
                flush();
            }
        };

        $this->response->withHeader('Content-Type', 'application/x-ndjson');
        $this->response->withHeader('Transfer-Encoding', 'chunked');
        $this->response->withBody($callback);

        return $this->response;
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
     * @deprecated Use jsonResponse() instead
     */
    public function renderJson($data, $statusCode = 200, $log = false)
    {
        $response = $this->jsonResponse($data, $statusCode, $log);
        $response->send();
        \Hiya::app()->end();
    }

    /**
     * @deprecated Use jsonError() instead
     */
    public function renderErrorJson($message, $statusCode = 400)
    {
        $response = $this->jsonError($message, $statusCode);
        $response->send();
        \Hiya::app()->end();
    }

    /**
     * @deprecated Use jsonSuccess() instead
     */
    public function renderSuccessJson($data = null, $message = 'Success')
    {
        $response = $this->jsonSuccess($data, $message);
        $response->send();
        \Hiya::app()->end();
    }

    // ============ CONTROLLER HELPERS ============

    /**
     * Redirect back to previous page
     * @param string $defaultUrl
     */
    public function redirectBack($defaultUrl = array('site/index'))
    {
        $backUrl = $this->request->getUrlReferrer();
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
        return $this->request->isAjax();
    }

    /**
     * Get request parameter with default
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getParam($name, $default = null)
    {
        return $this->request->input($name, $default);
    }

    // ============ REQUEST SHORTCUTS ============

    /**
     * Get input parameter
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function input($name, $default = null)
    {
        return $this->request->input($name, $default);
    }

    /**
     * Get POST parameter
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function post($name, $default = null)
    {
        return $this->request->post($name, $default);
    }

    /**
     * Get GET parameter
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function query($name, $default = null)
    {
        return $this->request->query($name, $default);
    }

    /**
     * Get JSON payload from request
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getJson($key = null, $default = null)
    {
        return $this->request->json($key, $default);
    }

    /**
     * Check if request has JSON
     *
     * @return bool
     */
    public function hasJson()
    {
        return $this->request->hasJson();
    }
}