<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Http;

/**
 * Response Interface
 * Inspired by PSR-7 ResponseInterface
 */
interface ResponseInterface
{
    /**
     * Get response body
     */
    public function getBody();
    
    /**
     * Get HTTP status code
     */
    public function getStatusCode();
    
    /**
     * Get all headers
     */
    public function getHeaders();
    
    /**
     * Get specific header
     */
    public function getHeader($name);
    
    /**
     * Set status code (fluent)
     */
    public function withStatus($code);
    
    /**
     * Set header (fluent)
     */
    public function withHeader($name, $value);
    
    /**
     * Set multiple headers (fluent)
     */
    public function withHeaders(array $headers);
    
    /**
     * Set body (fluent)
     */
    public function withBody($body);
    
    /**
     * Send response to browser
     */
    public function send();
    
    /**
     * Convert to string
     */
    public function __toString();
}