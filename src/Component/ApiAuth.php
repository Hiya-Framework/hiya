<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Component
 */

namespace Hiya\Component;

use CHttpException;
use Hiya;
use Hiya\Base\Component;
use Hiya\Component\IAuth;

/**
 * ApiAuth handles Bearer token validation for API requests.
 */
class ApiAuth extends Component implements IAuth
{
    /**
     * @var string|null The token key expected for authentication.
     */
    public $expectedToken = null;

    /**
     * Validates the Authorization header against the configured token key.
     * @return array Status data upon successful authorization
     * @throws CHttpException 401 if token is missing or invalid
     */
    public function authorize(): array
    {
        if ($this->expectedToken === null) {
            throw new \CException('ApiAuth component is not configured with an expectedToken.');
        }

        $request = Hiya::app()->request;
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
            throw new CHttpException(401, 'Invalid Authorization header.');
        }

        $token = str_replace('Bearer ', '', $authHeader);

        if ($token !== $this->expectedToken) {
            throw new CHttpException(401, 'Unauthorized access.');
        }

        return ['status' => 'authorized'];
    }
}