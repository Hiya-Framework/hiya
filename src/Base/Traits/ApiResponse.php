<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Base\Controller
 * @since 1.0
 * 
 * Api Controller
 */

namespace Hiya\Base\Traits;

trait ApiResponse
{
    protected function success($data = null, $message = 'Success', $status = 200)
    {
        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
    
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
    
    protected function jsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        \Hiya::app()->end();
    }
}