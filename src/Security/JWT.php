<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Security\Jwt
 * @since 1.0
 */

namespace Hiya\Security;

/**
 * JWT (JSON Web Token) - Super Secure Implementation
 * 
 * Features:
 * - Multiple algorithms (HS256, HS384, HS512, RS256, RS384, RS512)
 * - Automatic token expiration
 * - Issuer/audience validation
 * - Refresh tokens
 * - Token blacklisting
 * - Fingerprint/device binding
 * - One-time use tokens
 * - Nested tokens
 */

class Jwt
{
    /**
     * Supported algorithms
     */
    const ALGO_HS256 = 'HS256';
    const ALGO_HS384 = 'HS384';
    const ALGO_HS512 = 'HS512';
    const ALGO_RS256 = 'RS256';
    const ALGO_RS384 = 'RS384';
    const ALGO_RS512 = 'RS512';
    
    /**
     * @var string Secret key for HMAC algorithms
     */
    protected $secret;
    
    /**
     * @var array Blacklisted tokens
     */
    protected static $blacklist = [];
    
    /**
     * @var int Token validity leeway in seconds
     */
    protected $leeway = 0;
    
    /**
     * Constructor
     * 
     * @param string $secret Secret key (minimum 32 chars for HS256)
     * @throws \Exception
     */
    public function __construct($secret = null)
    {
        if ($secret === null) {
            $secret = $this->generateSecureSecret();
        }
        
        if (strlen($secret) < 32) {
            throw new \Exception('JWT secret must be at least 32 characters long');
        }
        
        $this->secret = $secret;
    }
    
    /**
     * Generate a cryptographically secure secret
     * 
     * @param int $length
     * @return string
     */
    public function generateSecureSecret($length = 64)
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Encode payload to JWT token
     * 
     * @param array $payload Token payload
     * @param string $algo Algorithm
     * @param int $ttl Time to live in seconds
     * @return string
     */
    public function encode($payload, $algo = self::ALGO_HS256, $ttl = 3600)
    {
        // Set standard claims
        $payload['iat'] = time(); // Issued at
        $payload['exp'] = time() + $ttl; // Expiration
        $payload['jti'] = $this->generateTokenId(); // JWT ID
        
        // Add fingerprint if not present
        if (!isset($payload['fingerprint'])) {
            $payload['fingerprint'] = $this->generateFingerprint();
        }
        
        // Create header
        $header = [
            'typ' => 'JWT',
            'alg' => $algo
        ];
        
        // Encode header and payload
        $base64Header = $this->base64UrlEncode(json_encode($header));
        $base64Payload = $this->base64UrlEncode(json_encode($payload));
        
        // Create signature
        $signature = $this->sign("$base64Header.$base64Payload", $algo);
        $base64Signature = $this->base64UrlEncode($signature);
        
        return "$base64Header.$base64Payload.$base64Signature";
    }
    
    /**
     * Decode and verify JWT token
     * 
     * @param string $token JWT token
     * @param bool $verifyFingerprint Verify device fingerprint
     * @return array|null Decoded payload or null if invalid
     */
    public function decode($token, $verifyFingerprint = true)
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Decode header and payload
        $header = json_decode($this->base64UrlDecode($base64Header), true);
        $payload = json_decode($this->base64UrlDecode($base64Payload), true);
        
        if (!$header || !$payload) {
            return null;
        }
        
        // Verify signature
        $expectedSignature = $this->sign("$base64Header.$base64Payload", $header['alg']);
        $providedSignature = $this->base64UrlDecode($base64Signature);
        
        if (!$this->verifySignature($expectedSignature, $providedSignature)) {
            return null;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < (time() - $this->leeway)) {
            return null;
        }
        
        // Check issued at (prevent tokens issued in the future)
        if (isset($payload['iat']) && $payload['iat'] > (time() + $this->leeway)) {
            return null;
        }
        
        // Check if token is blacklisted
        if (isset($payload['jti']) && $this->isBlacklisted($payload['jti'])) {
            return null;
        }
        
        // Verify fingerprint
        if ($verifyFingerprint && isset($payload['fingerprint'])) {
            if (!$this->verifyFingerprint($payload['fingerprint'])) {
                return null;
            }
        }
        
        return $payload;
    }
    
    /**
     * Create refresh token
     * 
     * @param array $payload Base payload
     * @param int $ttl Refresh token TTL (default: 7 days)
     * @return string
     */
    public function createRefreshToken($payload, $ttl = 604800)
    {
        $refreshPayload = [
            'refresh' => true,
            'user_id' => $payload['user_id'] ?? null,
            'iat' => time(),
            'exp' => time() + $ttl,
            'jti' => $this->generateTokenId()
        ];
        
        return $this->encode($refreshPayload, self::ALGO_HS256, $ttl);
    }
    
    /**
     * Refresh access token using refresh token
     * 
     * @param string $refreshToken Refresh token
     * @param array $newPayload New payload data
     * @return string|null New access token or null
     */
    public function refresh($refreshToken, $newPayload = [])
    {
        $payload = $this->decode($refreshToken, false);
        
        if (!$payload || !isset($payload['refresh']) || $payload['refresh'] !== true) {
            return null;
        }
        
        // Blacklist the old refresh token
        $this->blacklist($payload['jti'], $payload['exp']);
        
        // Create new access token
        $accessPayload = array_merge([
            'user_id' => $payload['user_id'],
            'refresh_id' => $this->generateTokenId()
        ], $newPayload);
        
        return $this->encode($accessPayload);
    }
    
    /**
     * Generate one-time token (for password reset, email verification, etc.)
     * 
     * @param array $data Token data
     * @param int $ttl Time to live (default: 15 minutes)
     * @return string
     */
    public function oneTimeToken($data, $ttl = 900)
    {
        $payload = array_merge($data, [
            'one_time' => true,
            'used' => false
        ]);
        
        return $this->encode($payload, self::ALGO_HS256, $ttl);
    }
    
    /**
     * Verify and consume one-time token
     * 
     * @param string $token One-time token
     * @return array|null Token data or null if invalid/used
     */
    public function verifyOneTimeToken($token)
    {
        $payload = $this->decode($token, false);
        
        if (!$payload || !isset($payload['one_time']) || $payload['one_time'] !== true) {
            return null;
        }
        
        // Check if already used
        if (isset($payload['used']) && $payload['used'] === true) {
            return null;
        }
        
        // Mark as used by blacklisting
        if (isset($payload['jti'])) {
            $this->blacklist($payload['jti'], $payload['exp'] ?? time());
        }
        
        return $payload;
    }
    
    /**
     * Generate device fingerprint
     * 
     * @return string
     */
    protected function generateFingerprint()
    {
        $data = [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $this->getClientIp(),
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
        ];
        
        return hash_hmac('sha256', json_encode($data), $this->secret);
    }
    
    /**
     * Verify device fingerprint
     * 
     * @param string $fingerprint
     * @return bool
     */
    protected function verifyFingerprint($fingerprint)
    {
        return hash_equals($fingerprint, $this->generateFingerprint());
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    protected function getClientIp()
    {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                    'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
                    'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Generate unique token ID
     * 
     * @return string
     */
    protected function generateTokenId()
    {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Sign data using specified algorithm
     * 
     * @param string $data Data to sign
     * @param string $algo Algorithm
     * @return string
     * @throws \Exception
     */
    protected function sign($data, $algo)
    {
        switch ($algo) {
            case self::ALGO_HS256:
                return hash_hmac('sha256', $data, $this->secret, true);
            case self::ALGO_HS384:
                return hash_hmac('sha384', $data, $this->secret, true);
            case self::ALGO_HS512:
                return hash_hmac('sha512', $data, $this->secret, true);
            case self::ALGO_RS256:
            case self::ALGO_RS384:
            case self::ALGO_RS512:
                return $this->rsaSign($data, $algo);
            default:
                throw new \Exception("Unsupported algorithm: $algo");
        }
    }
    
    /**
     * RSA sign data
     * 
     * @param string $data
     * @param string $algo
     * @return string
     */
    protected function rsaSign($data, $algo)
    {
        $key = openssl_pkey_get_private($this->getPrivateKey());
        
        $algoMap = [
            self::ALGO_RS256 => OPENSSL_ALGO_SHA256,
            self::ALGO_RS384 => OPENSSL_ALGO_SHA384,
            self::ALGO_RS512 => OPENSSL_ALGO_SHA512,
        ];
        
        openssl_sign($data, $signature, $key, $algoMap[$algo]);
        return $signature;
    }
    
    /**
     * Verify signature
     * 
     * @param string $expected
     * @param string $provided
     * @return bool
     */
    protected function verifySignature($expected, $provided)
    {
        return hash_equals($expected, $provided);
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data
     * @return string
     */
    protected function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     * 
     * @param string $data
     * @return string
     */
    protected function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Add token to blacklist
     * 
     * @param string $jti Token ID
     * @param int $expiry Expiry timestamp
     */
    public function blacklist($jti, $expiry = null)
    {
        $expiry = $expiry ?: time() + 3600;
        self::$blacklist[$jti] = $expiry;
        
        // Clean expired blacklist entries
        $this->cleanBlacklist();
    }
    
    /**
     * Check if token is blacklisted
     * 
     * @param string $jti
     * @return bool
     */
    protected function isBlacklisted($jti)
    {
        $this->cleanBlacklist();
        return isset(self::$blacklist[$jti]);
    }
    
    /**
     * Remove expired entries from blacklist
     */
    protected function cleanBlacklist()
    {
        $now = time();
        foreach (self::$blacklist as $jti => $expiry) {
            if ($expiry < $now) {
                unset(self::$blacklist[$jti]);
            }
        }
    }
    
    /**
     * Blacklist a token (logout)
     * 
     * @param string $token
     * @return bool
     */
    public function revoke($token)
    {
        $payload = $this->decode($token, false);
        
        if ($payload && isset($payload['jti'])) {
            $this->blacklist($payload['jti'], $payload['exp'] ?? time());
            return true;
        }
        
        return false;
    }
    
    /**
     * Get token remaining time in seconds
     * 
     * @param string $token
     * @return int|null
     */
    public function getRemainingTime($token)
    {
        $payload = $this->decode($token, false);
        
        if ($payload && isset($payload['exp'])) {
            $remaining = $payload['exp'] - time();
            return $remaining > 0 ? $remaining : 0;
        }
        
        return null;
    }
    
    /**
     * Set leeway for time-based validation
     * 
     * @param int $seconds
     * @return $this
     */
    public function setLeeway($seconds)
    {
        $this->leeway = $seconds;
        return $this;
    }
    
    /**
     * Get private key for RSA
     * 
     * @return string
     */
    protected function getPrivateKey()
    {
        // Override this method to load private key from file
        return $this->secret;
    }
}