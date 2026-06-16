<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Component\Jwt
 * @since 1.0
 */

namespace Hiya\Component;


/**
 * JWT Component for Hiya Framework
 * 
 * Usage:
 *   // Generate token
 *   $token = Hiya::app()->jwt->encode(['user_id' => 123]);
 *   
 *   // Verify token
 *   $payload = Hiya::app()->jwt->decode($token);
 *   
 *   // Logout (revoke token)
 *   Hiya::app()->jwt->revoke($token);
 */
class Jwt extends \CApplicationComponent
{
    /**
     * @var string Secret key for JWT (min 32 chars)
     */
    public $secret;
    
    /**
     * @var string Algorithm to use
     */
    public $algorithm = Jwt::ALGO_HS256;
    
    /**
     * @var int Token TTL in seconds (default: 1 hour)
     */
    public $ttl = 3600;
    
    /**
     * @var int Refresh token TTL in seconds (default: 7 days)
     */
    public $refreshTtl = 604800;
    
    /**
     * @var bool Verify device fingerprint
     */
    public $verifyFingerprint = true;
    
    /**
     * @var JWT instance
     */
    protected $_jwt;
    
    /**
     * Initialize component
     */
    public function init()
    {
        parent::init();
        
        // Generate secure secret if not provided
        if (empty($this->secret)) {
            $this->secret = $this->generateSecret();
        }
        
        $this->_jwt = new Jwt($this->secret);
    }
    
    /**
     * Generate secure secret
     * 
     * @return string
     */
    public function generateSecret()
    {
        return $this->_jwt->generateSecureSecret();
    }
    
    /**
     * Encode payload to JWT
     * 
     * @param array $payload
     * @param int|null $ttl
     * @return string
     */
    public function encode($payload, $ttl = null)
    {
        return $this->_jwt->encode($payload, $this->algorithm, $ttl ?? $this->ttl);
    }
    
    /**
     * Decode and verify JWT
     * 
     * @param string $token
     * @return array|null
     */
    public function decode($token)
    {
        return $this->_jwt->decode($token, $this->verifyFingerprint);
    }
    
    /**
     * Create refresh token
     * 
     * @param array $payload
     * @return string
     */
    public function createRefreshToken($payload)
    {
        return $this->_jwt->createRefreshToken($payload, $this->refreshTtl);
    }
    
    /**
     * Refresh access token
     * 
     * @param string $refreshToken
     * @param array $newPayload
     * @return string|null
     */
    public function refresh($refreshToken, $newPayload = [])
    {
        return $this->_jwt->refresh($refreshToken, $newPayload);
    }
    
    /**
     * Create one-time token (password reset, email verification)
     * 
     * @param array $data
     * @param int $ttl
     * @return string
     */
    public function oneTimeToken($data, $ttl = 900)
    {
        return $this->_jwt->oneTimeToken($data, $ttl);
    }
    
    /**
     * Verify one-time token
     * 
     * @param string $token
     * @return array|null
     */
    public function verifyOneTimeToken($token)
    {
        return $this->_jwt->verifyOneTimeToken($token);
    }
    
    /**
     * Revoke token (logout)
     * 
     * @param string $token
     * @return bool
     */
    public function revoke($token)
    {
        return $this->_jwt->revoke($token);
    }
    
    /**
     * Get token remaining time
     * 
     * @param string $token
     * @return int|null
     */
    public function getRemainingTime($token)
    {
        return $this->_jwt->getRemainingTime($token);
    }
    
    /**
     * Check if token is valid
     * 
     * @param string $token
     * @return bool
     */
    public function validate($token)
    {
        return $this->decode($token) !== null;
    }
    
    /**
     * Get authenticated user ID from token
     * 
     * @param string $token
     * @return int|null
     */
    public function getUserId($token)
    {
        $payload = $this->decode($token);
        return $payload['user_id'] ?? null;
    }
    
    /**
     * Delegate to JWT instance
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->_jwt, $name)) {
            return call_user_func_array([$this->_jwt, $name], $arguments);
        }
        
        throw new \BadMethodCallException("Method '{$name}' not found in JwtComponent");
    }
}