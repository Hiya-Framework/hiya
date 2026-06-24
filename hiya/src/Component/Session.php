<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Component;

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
     * CSRF token key (not encrypted)
     */
    const CSRF_KEY = '_csrf_token';
    
    /**
     * Prefix for encrypted session keys
     */
    const ENCRYPTED_PREFIX = '_enc_';
    
    /**
     * Session config options
     */
    protected $config = [
        'secure' => true,           // Force secure cookie (HTTPS only)
        'httponly' => true,          // Prevent JavaScript access
        'samesite' => 'Lax',         // CSRF protection: Strict, Lax, None
        'lifetime' => 0,             // 0 = until browser closes
        'encrypt_all' => true,       // Encrypt ALL session data (default: true)
        'regenerate_timeout' => 300,  // Regenerate session ID every N seconds
        'csrf_regenerate' => true,    // Regenerate CSRF token on each request
        'encryption_key' => null,     // Custom encryption key
        'encrypt_exceptions' => [    // Keys that should NOT be encrypted
            '_last_regeneration',
            '_encryption_key',
        ],
    ];
    
    
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
        if (getenv('SESSION_ENCRYPT_ALL') !== false) {
            $this->config['encrypt_all'] = getenv('SESSION_ENCRYPT_ALL') === 'true';
        }
        if (getenv('APP_KEY')) {
            $this->config['encryption_key'] = getenv('APP_KEY');
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
        $this->config = array_merge($this->config, $this->config);
        $this->applyConfig();

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
        
        // Decrypt all session data on init
        $this->decryptAllSessionData();
    }
    
    /**
     * Set cookie parameters based on config
     * Signature must be compatible with parent: setCookieParams($value)
     * 
     * @param array|null $value Cookie parameters (optional, for parent compatibility)
     */
    public function setCookieParams($value = null)
    {
        // If custom params provided via parameter, use them directly
        if ($value !== null && is_array($value)) {
            parent::setCookieParams($value);
            return;
        }
        
        // Use config
        $secure = $this->config['secure'];
        
        $params = [
            'httponly' => $this->config['httponly'],
            'lifetime' => $this->config['lifetime'],
            'path' => '/',
            'secure' => $secure,
            'samesite' => $this->config['samesite'],
        ];
        
        // Call parent with array
        parent::setCookieParams($params);
        
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
        $this->setRaw(self::CSRF_KEY, $token);
        return $token;
    }
    
    /**
     * Get CSRF token
     */
    public function getCsrfToken()
    {
        return $this->getRaw(self::CSRF_KEY);
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
     * Check if a key should be encrypted
     */
    protected function shouldEncrypt($key)
    {
        if (!$this->config['encrypt_all']) {
            return false;
        }
        
        // Skip encryption for exceptions
        if (in_array($key, $this->config['encrypt_exceptions'])) {
            return false;
        }
        
        // Skip CSRF token
        if ($key === self::CSRF_KEY) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if a key is encrypted (starts with prefix)
     */
    protected function isEncryptedKey($key)
    {
        return strpos($key, self::ENCRYPTED_PREFIX) === 0;
    }
    
    /**
     * Get original key name
     */
    protected function getOriginalKey($encryptedKey)
    {
        return substr($encryptedKey, strlen(self::ENCRYPTED_PREFIX));
    }
    
    /**
     * Get encrypted key name
     */
    protected function getEncryptedKey($originalKey)
    {
        return self::ENCRYPTED_PREFIX . $originalKey;
    }
    
    /**
     * Set raw value (without encryption)
     */
    protected function setRaw($key, $value)
    {
        parent::add($key, $value);
    }
    
    /**
     * Get raw value (without decryption)
     */
    protected function getRaw($key, $defaultValue = null)
    {
        return parent::get($key, $defaultValue);
    }
    
    /**
     * Set session value with automatic encryption
     */
    public function set($key, $value)
    {
        if ($this->shouldEncrypt($key)) {
            $encryptedKey = $this->getEncryptedKey($key);
            $encryptedValue = $this->encrypt($value);
            $this->setRaw($encryptedKey, $encryptedValue);
        } else {
            $this->setRaw($key, $value);
        }
        return $this;
    }
    
    /**
     * Get session value with automatic decryption
     */
    public function get($key, $defaultValue = null)
    {
        // Check if key is encrypted
        if ($this->isEncryptedKey($key)) {
            $originalKey = $this->getOriginalKey($key);
            $value = $this->getRaw($key, null);
            
            if ($value !== null) {
                return $this->decrypt($value);
            }
            return $defaultValue;
        }
        
        // Check if value should be encrypted
        if ($this->shouldEncrypt($key)) {
            $encryptedKey = $this->getEncryptedKey($key);
            $value = $this->getRaw($encryptedKey, null);
            
            if ($value !== null) {
                return $this->decrypt($value);
            }
            return $defaultValue;
        }
        
        // Regular key
        return $this->getRaw($key, $defaultValue);
    }
    
    /**
     * Check if session key exists
     */
    public function has($key)
    {
        if ($this->shouldEncrypt($key)) {
            return $this->hasRaw($this->getEncryptedKey($key));
        }
        return $this->hasRaw($key);
    }
    
    /**
     * Check raw key exists
     */
    protected function hasRaw($key)
    {
        return parent::contains($key);
    }
    
    /**
     * Delete session key
     */
    public function delete($key)
    {
        if ($this->shouldEncrypt($key)) {
            $this->deleteRaw($this->getEncryptedKey($key));
        }
        $this->deleteRaw($key);
        return $this;
    }
    
    /**
     * Delete raw key
     */
    protected function deleteRaw($key)
    {
        parent::remove($key);
    }
    
    /**
     * Decrypt all session data on load
     */
    protected function decryptAllSessionData()
    {
        if (!$this->config['encrypt_all']) {
            return;
        }
        
        $keys = array_keys($_SESSION);
        foreach ($keys as $key) {
            if ($this->isEncryptedKey($key)) {
                $originalKey = $this->getOriginalKey($key);
                $value = $this->getRaw($key, null);
                
                if ($value !== null) {
                    try {
                        $decrypted = $this->decrypt($value);
                        // Store decrypted value for easy access
                        $_SESSION[$originalKey] = $decrypted;
                        // Keep encrypted version for storage
                    } catch (\Exception $e) {
                        // Decryption failed, keep as is
                    }
                }
            }
        }
    }
    
    /**
     * Get all session data (decrypted)
     */
    public function all()
    {
        $result = [];
        $keys = array_keys($_SESSION);
        
        foreach ($keys as $key) {
            if ($this->isEncryptedKey($key)) {
                $originalKey = $this->getOriginalKey($key);
                $value = $this->getRaw($key, null);
                if ($value !== null) {
                    try {
                        $result[$originalKey] = $this->decrypt($value);
                    } catch (\Exception $e) {
                        $result[$originalKey] = $value;
                    }
                }
            } elseif (!in_array($key, $this->config['encrypt_exceptions']) && $key !== self::CSRF_KEY) {
                $result[$key] = $this->getRaw($key, null);
            }
        }
        
        return $result;
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
        $flash = $this->getRaw(self::FLASH_KEY, []);
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
     * Set user data (always encrypted)
     */
    public function setUser($key, $value)
    {
        return $this->set(self::USER_PREFIX . $key, $value);
    }
    
    /**
     * Get user data (always decrypted)
     */
    public function getUser($key, $defaultValue = null)
    {
        return $this->get(self::USER_PREFIX . $key, $defaultValue);
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
        
        // Store full user data (encrypted)
        $this->set('user_data', $user);
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
        return [
            'id' => $this->get('user_id'),
            'name' => $this->get('user_name'),
            'email' => $this->get('user_email'),
            'role' => $this->get('user_role'),
            'data' => $this->get('user_data'),
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
     */
    public function setEncryptionKey($key)
    {
        $this->config['encryption_key'] = $key;
        return $this;
    }
    
    /**
     * Get encryption key from multiple sources
     */
    protected function getEncryptionKey()
    {
        if (!empty($this->config['encryption_key'])) {
            $key = $this->config['encryption_key'];
        } else {
            $key = $this->getOrCreateSessionKey();
        }
        
        return hash('sha256', $key, true);
    }
    
    /**
     * Get or create session-specific encryption key
     */
    protected function getOrCreateSessionKey()
    {
        $key = $this->getRaw('_encryption_key');
        
        if (!$key) {
            $key = bin2hex(random_bytes(32));
            $this->setRaw('_encryption_key', $key);
        }
        
        return $key;
    }
    
    /**
     * Encrypt data with AES-256-CBC
     */
    protected function encrypt($data)
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt(serialize($data), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $ciphertext);
    }
    
    /**
     * Decrypt data with AES-256-CBC
     */
    protected function decrypt($data)
    {
        $key = $this->getEncryptionKey();
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        return unserialize(openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv));
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
                $this->deleteRaw('_encryption_key');
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