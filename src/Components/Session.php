<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Components\Session
 * @since 1.0
 */

namespace Hiya\Components;

class Session extends \CHttpSession
{
    /**
     * Session instance
     */
    private static $instance = null;
    
    /**
     * Flash message key
     */
    const FLASH_KEY = '_flash_messages';
    
    /**
     * User session prefix
     */
    const USER_PREFIX = '_user_';
    
    /**
     * CSRF token key
     */
    const CSRF_KEY = '_csrf_token';
    
    /**
     * Session config options
     */
    protected $config = [
        'secure' => false,           // Force secure cookie (HTTPS only)
        'httponly' => true,          // Prevent JavaScript access
        'samesite' => 'Lax',         // CSRF protection: Strict, Lax, None
        'lifetime' => 0,             // 0 = until browser closes
        'encrypt_user_data' => false, // Encrypt user data in session
        'regenerate_timeout' => 300,  // Regenerate session ID every N seconds
        'csrf_regenerate' => true,    // Regenerate CSRF token on each request
        'encryption_key' => null,     // Custom encryption key (set via session->encryption_key)
    ];
    
    /**
     * Constructor
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->applyConfig();
        $this->init();
    }
    
    /**
     * Apply configuration from params or env
     */
    protected function applyConfig()
    {
        // Load from Yii params if available
        if (\Yii::app() && isset(\Yii::app()->params['session'])) {
            $this->config = array_merge($this->config, \Yii::app()->params['session']);
        }
        
        // Load from environment variables (override)
        if (getenv('SESSION_SECURE') !== false) {
            $this->config['secure'] = getenv('SESSION_SECURE') === 'true';
        }
        if (getenv('SESSION_HTTPONLY') !== false) {
            $this->config['httponly'] = getenv('SESSION_HTTPONLY') === 'true';
        }
        if (getenv('SESSION_SAMESITE')) {
            $this->config['samesite'] = getenv('SESSION_SAMESITE');
        }
        if (getenv('SESSION_LIFETIME')) {
            $this->config['lifetime'] = (int)getenv('SESSION_LIFETIME');
        }
        if (getenv('SESSION_ENCRYPT') !== false) {
            $this->config['encrypt_user_data'] = getenv('SESSION_ENCRYPT') === 'true';
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance($config = [])
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Initialize session
     */
    public function init()
    {
        parent::init();
        
        // Set cookie parameters
        $this->setCookieParams();
        
        // Start session if not started
        if (!$this->getIsStarted()) {
            @$this->open();
        }
        
        // Regenerate session ID periodically
        $this->regenerateSessionIdIfNeeded();
        
        // Initialize CSRF token
        if (!$this->has(self::CSRF_KEY)) {
            $this->generateCsrfToken();
        } elseif ($this->config['csrf_regenerate']) {
            $this->generateCsrfToken();
        }
    }
    
    /**
     * Set cookie parameters based on config
     */
    protected function setCookieParams()
    {
        // Auto-detect HTTPS
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        
        // Determine secure flag
        $secure = $this->config['secure'];
        if (!$secure && $isHttps) {
            $secure = true;
        }
        
        $cookieParams = [
            'httponly' => $this->config['httponly'],
            'lifetime' => $this->config['lifetime'],
            'path' => '/',
            'secure' => $secure,
            'samesite' => $this->config['samesite'],
        ];
        
        if (method_exists($this, 'setCookieParams')) {
            $this->setCookieParams($cookieParams);
        }
        
        // Set PHP ini settings
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.cookie_httponly', $this->config['httponly'] ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_cookies', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', $this->config['samesite']);
            
            if ($secure) {
                ini_set('session.cookie_secure', 1);
            }
        }
    }
    
    /**
     * Regenerate session ID periodically
     */
    protected function regenerateSessionIdIfNeeded()
    {
        $timeout = $this->config['regenerate_timeout'];
        if ($timeout <= 0) {
            return;
        }
        
        $lastRegen = $this->get('_last_regeneration', 0);
        $currentTime = time();
        
        if ($currentTime - $lastRegen > $timeout) {
            $this->regenerateID(true);
            $this->set('_last_regeneration', $currentTime);
        }
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerateID($deleteOldSession = true)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            parent::regenerateID($deleteOldSession);
        }
        return $this;
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken()
    {
        $token = bin2hex(random_bytes(32));
        $this->set(self::CSRF_KEY, $token);
        return $token;
    }
    
    /**
     * Get CSRF token
     */
    public function getCsrfToken()
    {
        return $this->get(self::CSRF_KEY);
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCsrfToken($token)
    {
        if (empty($token)) {
            return false;
        }
        
        $storedToken = $this->getCsrfToken();
        return hash_equals($storedToken, $token);
    }
    
    /**
     * Set session value
     */
    public function set($key, $value)
    {
        $this->add($key, $value);
        return $this;
    }
    
    /**
     * Get session value
     */
    public function get($key, $defaultValue = null)
    {
        return parent::get($key, $defaultValue);
    }
    
    /**
     * Check if session key exists
     */
    public function has($key)
    {
        return parent::contains($key);
    }
    
    /**
     * Delete session key
     */
    public function delete($key)
    {
        parent::remove($key);
        return $this;
    }
    
    /**
     * Get all session data
     */
    public function all()
    {
        return parent::toArray();
    }
    
    /**
     * Clear all session data
     */
    public function clear()
    {
        parent::clear();
        $this->generateCsrfToken();
        return $this;
    }
    
    /**
     * Destroy session completely
     */
    public function destroy()
    {
        $this->clear();
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Delete session cookie
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
        
        return $this;
    }
    
    /**
     * Set flash message (deleted after next request)
     */
    public function setFlash($key, $value)
    {
        $flash = $this->get(self::FLASH_KEY, []);
        $flash[$key] = $value;
        $this->set(self::FLASH_KEY, $flash);
        return $this;
    }
    
    /**
     * Get flash message and delete it
     */
    public function getFlash($key, $defaultValue = null)
    {
        $flash = $this->get(self::FLASH_KEY, []);
        $value = isset($flash[$key]) ? $flash[$key] : $defaultValue;
        
        if (isset($flash[$key])) {
            unset($flash[$key]);
            $this->set(self::FLASH_KEY, $flash);
        }
        
        return $value;
    }
    
    /**
     * Check if flash message exists
     */
    public function hasFlash($key)
    {
        $flash = $this->get(self::FLASH_KEY, []);
        return isset($flash[$key]);
    }
    
    /**
     * Get all flash messages
     */
    public function getAllFlashes()
    {
        $flash = $this->get(self::FLASH_KEY, []);
        $this->delete(self::FLASH_KEY);
        return $flash;
    }
    
    /**
     * Keep flash message for next request
     */
    public function keepFlash($key)
    {
        $flash = $this->get(self::FLASH_KEY, []);
        if (isset($flash[$key])) {
            $this->setFlash($key, $flash[$key]);
        }
        return $this;
    }
    
    /**
     * Set user data (with optional encryption)
     */
    public function setUser($key, $value)
    {
        $encrypt = $this->config['encrypt_user_data'];
        
        if ($encrypt) {
            $value = $this->encrypt($value);
        }
        
        return $this->set(self::USER_PREFIX . $key, $value);
    }
    
    /**
     * Get user data (with optional decryption)
     */
    public function getUser($key, $defaultValue = null)
    {
        $value = $this->get(self::USER_PREFIX . $key, $defaultValue);
        
        if ($this->config['encrypt_user_data'] && $value !== null && $value !== $defaultValue) {
            $value = $this->decrypt($value);
        }
        
        return $value;
    }
    
    /**
     * Check if user data exists
     */
    public function hasUser($key)
    {
        return $this->has(self::USER_PREFIX . $key);
    }
    
    /**
     * Delete user data
     */
    public function deleteUser($key)
    {
        return $this->delete(self::USER_PREFIX . $key);
    }
    
    /**
     * Set authenticated user
     */
    public function setAuthUser($user)
    {
        $userId = is_array($user) ? $user['id'] : $user->id;
        
        $this->set('user_id', $userId);
        $this->set('user_name', is_array($user) ? $user['name'] : $user->name);
        $this->set('user_email', is_array($user) ? $user['email'] : $user->email);
        $this->set('user_role', is_array($user) ? ($user['role'] ?? 'user') : ($user->role ?? 'user'));
        
        // Store full user data (optionally encrypted)
        if ($this->config['encrypt_user_data']) {
            $this->set('user_data', $this->encrypt($user));
        } else {
            $this->set('user_data', $user);
        }
        
        $this->set('logged_in', true);
        
        // Regenerate session ID on login for security
        $this->regenerateID(true);
        
        return $this;
    }
    
    /**
     * Get authenticated user
     */
    public function getUserAuth()
    {
        $userData = $this->get('user_data');
        
        if ($this->config['encrypt_user_data'] && $userData) {
            $userData = $this->decrypt($userData);
        }
        
        return [
            'id' => $this->get('user_id'),
            'name' => $this->get('user_name'),
            'email' => $this->get('user_email'),
            'role' => $this->get('user_role'),
            'data' => $userData,
            'isLoggedIn' => $this->isLoggedIn()
        ];
    }
    
    /**
     * Get user ID
     */
    public function getUserId()
    {
        return $this->get('user_id');
    }
    
    /**
     * Get user name
     */
    public function getUserName()
    {
        return $this->get('user_name');
    }
    
    /**
     * Get user email
     */
    public function getUserEmail()
    {
        return $this->get('user_email');
    }
    
    /**
     * Get user role
     */
    public function getUserRole()
    {
        return $this->get('user_role');
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        return $this->get('logged_in', false) && $this->getUserId() !== null;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->get('user_role') === 'admin';
    }
    
    /**
     * Logout user
     */
    public function logout()
    {
        $this->delete('user_id');
        $this->delete('user_name');
        $this->delete('user_email');
        $this->delete('user_role');
        $this->delete('user_data');
        $this->delete('logged_in');
        
        // Regenerate session ID on logout
        $this->regenerateID(true);
        
        return $this;
    }
    
    /**
     * Set encryption key (can be called at runtime)
     * 
     * @param string $key Custom encryption key
     * @return $this
     */
    public function setEncryptionKey($key)
    {
        $this->config['encryption_key'] = $key;
        return $this;
    }
    
    /**
     * Get encryption key from multiple sources
     * 
     * Priority order:
     * 1. Custom key set via setEncryptionKey()
     * 2. APP_KEY constant
     * 3. Environment variable APP_KEY
     * 4. Session-based key (auto-generated)
     * 5. Default fallback key
     */
    protected function getEncryptionKey()
    {
        // 1. Check custom encryption key set via setEncryptionKey()
        if (!empty($this->config['encryption_key'])) {
            $key = $this->config['encryption_key'];
        }
        // 4. Get or create session-based key
        else {
            $key = $this->getOrCreateSessionKey();
        }
        
        return hash('sha256', $key, true);
    }
    
    /**
     * Get or create session-specific encryption key
     */
    protected function getOrCreateSessionKey()
    {
        $key = $this->get('_encryption_key');
        
        if (!$key) {
            $key = bin2hex(random_bytes(32));
            $this->set('_encryption_key', $key);
            
            // Log warning in production
            if (!defined('YII_DEBUG') || !YII_DEBUG) {
                \Yii::log('Using auto-generated session encryption key', 'warning', 'session');
            }
        }
        
        return $key;
    }
    
    /**
     * Simple encryption for sensitive data
     */
    protected function encrypt($data)
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt(serialize($data), 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $ciphertext);
    }
    
    /**
     * Simple decryption for sensitive data
     */
    protected function decrypt($data)
    {
        $key = $this->getEncryptionKey();
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        return unserialize(openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv));
    }
    
    /**
     * Flash message helpers
     */
    public function success($message)
    {
        return $this->setFlash('success', $message);
    }
    
    public function error($message)
    {
        return $this->setFlash('error', $message);
    }
    
    public function warning($message)
    {
        return $this->setFlash('warning', $message);
    }
    
    public function info($message)
    {
        return $this->setFlash('info', $message);
    }
    
    public function getSuccess()
    {
        return $this->getFlash('success');
    }
    
    public function getError()
    {
        return $this->getFlash('error');
    }
    
    public function getWarning()
    {
        return $this->getFlash('warning');
    }
    
    public function getInfo()
    {
        return $this->getFlash('info');
    }
    
    /**
     * Set session name
     */
    public function setSessionName($name)
    {
        session_name($name);
        return $this;
    }
    
    /**
     * Get session name
     */
    public function getSessionName()
    {
        return session_name();
    }
    
    /**
     * Set session timeout
     */
    public function setTimeout($seconds)
    {
        $this->config['lifetime'] = $seconds;
        $this->setCookieParams();
        return $this;
    }
    
    /**
     * Get session lifetime
     */
    public function getLifetime()
    {
        return $this->config['lifetime'];
    }
    
    /**
     * Update configuration at runtime
     */
    public function setConfig($key, $value)
    {
        if (isset($this->config[$key])) {
            $this->config[$key] = $value;
            
            if ($key === 'encryption_key') {
                // Clear cached session key when encryption key changes
                $this->delete('_encryption_key');
            }
            
            $this->setCookieParams();
        }
        return $this;
    }
    
    /**
     * Get current configuration
     */
    public function getConfig()
    {
        return $this->config;
    }
}