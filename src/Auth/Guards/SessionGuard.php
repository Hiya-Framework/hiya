<?php
namespace Hiya\Auth\Guards;

use Hiya\Auth\Gate;

/**
 * Session Guard - Authentication & Authorization
 * 
 * Manage authentication (login/logout) and authorization (Gate)
 * Integrated session PHP
 */
class SessionGuard
{
    /**
     * @var string Session key for user
     */
    protected $sessionKey = '__HIYA_user';
    
    /**
     * @var string Session key for token
     */
    protected $tokenKey = '__HIYA_token';
    
    /**
     * @var mixed Current user
     */
    protected $user;
    
    /**
     * @var Gate Gate instance
     */
    protected $gate;
    
    /**
     * @var bool Whether user is authenticated
     */
    protected $authenticated = false;
    
    /**
     * @var callable User provider callback
     */
    protected $userProvider;
    
    /**
     * @var array Login attempt callbacks
     */
    protected $loginCallbacks = [];
    
    /**
     * @var array Logout callbacks
     */
    protected $logoutCallbacks = [];
    
    /**
     * Constructor
     * 
     * @param Gate $gate Gate instance
     * @param callable|null $userProvider User provider callback
     */
    public function __construct(Gate $gate = null, callable $userProvider = null)
    {
        $this->gate = $gate ?: new Gate();
        $this->userProvider = $userProvider;
        
        $this->initSession();
    }
    
    /**
     * Initialize session
     */
    protected function initSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->loadUserFromSession();
    }
    
    /**
     * Load user from session
     */
    protected function loadUserFromSession()
    {
        if (isset($_SESSION[$this->sessionKey]) && $this->userProvider) {
            $userId = $_SESSION[$this->sessionKey];
            $user = call_user_func($this->userProvider, $userId);
            
            if ($user) {
                $this->user = $user;
                $this->authenticated = true;
                $this->gate->forUser($user);
            }
        }
    }
    
    /**
     * Set user provider
     * 
     * @param callable $provider
     * @return $this
     */
    public function setUserProvider(callable $provider)
    {
        $this->userProvider = $provider;
        return $this;
    }
    
    /**
     * Attempt to login
     * 
     * @param string $username
     * @param string $password
     * @param callable $authenticator Custom authentication logic
     * @param bool $remember Remember user (not implemented yet)
     * @return bool
     */
    public function attempt($username, $password, callable $authenticator = null, $remember = false)
    {
        $user = null;
        
        if ($authenticator) {
            $user = call_user_func($authenticator, $username, $password);
        }
        
        if (!$user) {
            return false;
        }
        
        $this->login($user, $remember);
        
        // Fire login callbacks
        foreach ($this->loginCallbacks as $callback) {
            call_user_func($callback, $user);
        }
        
        return true;
    }
    
    /**
     * Login user
     * 
     * @param mixed $user
     * @param bool $remember
     * @return $this
     */
    public function login($user, $remember = false)
    {
        $this->user = $user;
        $this->authenticated = true;
        $this->gate->forUser($user);
        
        // Store in session
        $userId = is_object($user) ? ($user->id ?? $user->getId()) : $user;
        $_SESSION[$this->sessionKey] = $userId;
        
        return $this;
    }
    
    /**
     * Login using ID
     * 
     * @param string|int $id
     * @return bool
     */
    public function loginUsingId($id)
    {
        if (!$this->userProvider) {
            return false;
        }
        
        $user = call_user_func($this->userProvider, $id);
        
        if (!$user) {
            return false;
        }
        
        $this->login($user);
        return true;
    }
    
    /**
     * Logout user
     */
    public function logout()
    {
        // Fire logout callbacks
        foreach ($this->logoutCallbacks as $callback) {
            call_user_func($callback, $this->user);
        }
        
        $this->user = null;
        $this->authenticated = false;
        
        unset($_SESSION[$this->sessionKey]);
        unset($_SESSION[$this->tokenKey]);
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public function check()
    {
        return $this->authenticated;
    }
    
    /**
     * Check if user is guest
     * 
     * @return bool
     */
    public function guest()
    {
        return !$this->authenticated;
    }
    
    /**
     * Get current user
     * 
     * @return mixed|null
     */
    public function user()
    {
        return $this->user;
    }
    
    /**
     * Get user ID
     * 
     * @return string|int|null
     */
    public function id()
    {
        return $this->user ? ($this->user->id ?? $this->user->getId()) : null;
    }
    
    /**
     * Set token for API authentication
     * 
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $_SESSION[$this->tokenKey] = $token;
        return $this;
    }
    
    /**
     * Get token
     * 
     * @return string|null
     */
    public function getToken()
    {
        return $_SESSION[$this->tokenKey] ?? null;
    }
    
    /**
     * Validate user credentials
     * 
     * @param string $username
     * @param string $password
     * @param callable $validator
     * @return bool
     */
    public function validate($username, $password, callable $validator)
    {
        return call_user_func($validator, $username, $password);
    }
    
    /**
     * Register login callback
     * 
     * @param callable $callback
     * @return $this
     */
    public function onLogin(callable $callback)
    {
        $this->loginCallbacks[] = $callback;
        return $this;
    }
    
    /**
     * Register logout callback
     * 
     * @param callable $callback
     * @return $this
     */
    public function onLogout(callable $callback)
    {
        $this->logoutCallbacks[] = $callback;
        return $this;
    }
    
    /**
     * Get Gate instance for authorization
     * 
     * @return Gate
     */
    public function gate()
    {
        return $this->gate;
    }
    
    /**
     * Check permission (delegate to Gate)
     * 
     * @param string $ability
     * @param array $arguments
     * @return bool
     */
    public function can($ability, ...$arguments)
    {
        return $this->gate->check($ability, ...$arguments);
    }
    
    /**
     * Check cannot permission
     * 
     * @param string $ability
     * @param array $arguments
     * @return bool
     */
    public function cannot($ability, ...$arguments)
    {
        return $this->gate->denies($ability, ...$arguments);
    }
    
    /**
     * Authorize action
     * 
     * @param string $ability
     * @param array $arguments
     * @return \Hiya\Auth\Access\Response
     */
    public function authorize($ability, ...$arguments)
    {
        return $this->gate->authorize($ability, ...$arguments);
    }
    
    /**
     * Define ability
     * 
     * @param string $ability
     * @param callable $callback
     * @return $this
     */
    public function define($ability, callable $callback)
    {
        $this->gate->define($ability, $callback);
        return $this;
    }
    
    /**
     * Register policy
     * 
     * @param string $class
     * @param string $policy
     * @return $this
     */
    public function policy($class, $policy)
    {
        $this->gate->policy($class, $policy);
        return $this;
    }
    
    /**
     * Magic method for permission checking (canXXX)
     * 
     * @param string $name
     * @param array $arguments
     * @return bool
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'can') === 0) {
            $ability = lcfirst(substr($name, 3));
            return $this->can($ability, ...$arguments);
        }
        
        if (strpos($name, 'cannot') === 0) {
            $ability = lcfirst(substr($name, 6));
            return $this->cannot($ability, ...$arguments);
        }
        
        throw new \BadMethodCallException("Method {$name} does not exist.");
    }
}