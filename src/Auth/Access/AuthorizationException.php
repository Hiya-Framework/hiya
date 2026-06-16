<?php
namespace Hiya\Auth\Access;

class AuthorizationException extends \Exception
{
    protected ?int $statusCode;
    
    public function __construct($message = null, ?int $statusCode = null)
    {
        parent::__construct($message ?: 'This action is unauthorized.', 403);
        $this->statusCode = $statusCode;
    }
    
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}