<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Component
 * @since 1.0
 */

namespace Hiya\Component;

use CHttpException;
use Hiya\Base\Component;
use Hiya\Component\IAuth;

/**
 * ApiAuth handles Bearer token validation for API requests.
 * * HOW TO USE:
 * 1. Register in main.config:
 * 'components' => [
 * 'apiAuth' => ['class' => 'Hiya\Component\ApiAuth'],
 * ],
 * * 2. Enforce in ApiController:
 * protected function beforeAction($action) {
 * if (!in_array($action->id, ['login'])) {
 * $this->user = \Yii::app()->apiAuth->authorize();
 * }
 * return parent::beforeAction($action);
 * }
 * * 3. Custom Override:
 * To change auth logic, create a new class implementing IAuth 
 * and update the 'class' definition in main.config.
 */
class ApiAuth extends Component implements IAuth
{
    /**
     * Initialize the component. 
     * Yii calls this automatically when the component is loaded.
     */
    public function init()
    {
        parent::init();
    }
    
    /**
     * Validates the Authorization header from the request.
     * Expects a Bearer token formatted as base64(json_string).
     * * @return array Decoded token data containing user/bot claims
     * @throws CHttpException 401 if token is missing, invalid, or expired
     */
    public function authorize()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        // Check if header exists and starts with 'Bearer '
        if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
            throw new \CHttpException(401, 'Authorization header missing or invalid.');
        }

        // Extract and decode the base64 token
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = base64_decode($token, true);
        
        if (!$decoded) {
            throw new \CHttpException(401, 'Invalid token encoding.');
        }

        // Decode JSON payload
        $data = json_decode($decoded, true);

        // Security: Check expiry claim
        if (!isset($data['expires']) || $data['expires'] < time()) {
            throw new \CHttpException(401, 'Token expired.');
        }

        return $data;
    }
}