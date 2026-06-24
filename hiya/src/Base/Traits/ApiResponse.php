<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Base\Traits;

use Hiya\Base\Resource;
use Hiya\Base\ResourceCollection;

trait ApiResponse
{
    /**
     * Success response with Laravel-style
     */
    protected function success($data = null, $message = 'Success', $status = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        // Handle Resource/Collection
        if ($data instanceof Resource) {
            $response['data'] = $data->resolve();
        } elseif ($data instanceof ResourceCollection) {
            $result = $data->resolve();
            $response['data'] = $result['data'] ?? [];
            if (!empty($result['meta'])) {
                $response['meta'] = $result['meta'];
            }
        } else {
            $response['data'] = $data;
        }

        return $this->jsonResponse($response, $status);
    }

    /**
     * Error response
     */
    protected function error($message = 'Error', $status = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return $this->jsonResponse($response, $status);
    }

    /**
     * Not found response
     */
    protected function notFound($message = 'Resource not found')
    {
        return $this->error($message, 404);
    }

    /**
     * Created response
     */
    protected function created($data = null, $message = 'Resource created')
    {
        return $this->success($data, $message, 201);
    }

    /**
     * No content response
     */
    protected function noContent($message = 'No content')
    {
        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
        ], 204);
    }

    /**
     * Validation error
     */
    protected function validationError($errors, $message = 'Validation failed')
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorized($message = 'Unauthorized')
    {
        return $this->error($message, 401);
    }

    /**
     * Forbidden response
     */
    protected function forbidden($message = 'Forbidden')
    {
        return $this->error($message, 403);
    }

    /**
     * JSON response
     */
    protected function jsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        \Hiya::app()->end();
    }
}