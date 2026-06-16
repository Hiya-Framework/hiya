<?php
namespace Hiya\Auth;

use Hiya\Auth\Access\Response;

class Gate
{
    protected $abilities = [];
    protected $beforeCallbacks = [];
    protected $afterCallbacks = [];
    protected $policies = [];
    protected $user;
    
    public function forUser($user)
    {
        $this->user = $user;
        return $this;
    }
    
    public function define($ability, callable $callback)
    {
        $this->abilities[$ability] = $callback;
        return $this;
    }
    
    public function before(callable $callback)
    {
        $this->beforeCallbacks[] = $callback;
        return $this;
    }
    
    public function after(callable $callback)
    {
        $this->afterCallbacks[] = $callback;
        return $this;
    }
    
    public function policy($class, $policy)
    {
        $this->policies[$class] = $policy;
        return $this;
    }
    
    protected function getPolicyFor($class)
    {
        $className = is_object($class) ? get_class($class) : $class;
        
        if (isset($this->policies[$className])) {
            return $this->policies[$className];
        }
        
        foreach ($this->policies as $key => $policy) {
            if (is_subclass_of($className, $key)) {
                return $policy;
            }
        }
        
        return null;
    }
    
    protected function callPolicyMethod($policy, $method, $user, ...$arguments)
    {
        $policyInstance = is_string($policy) ? new $policy() : $policy;
        
        if (method_exists($policyInstance, $method)) {
            $result = $policyInstance->{$method}($user, ...$arguments);
            if ($result instanceof Response) {
                return $result;
            }
            return $result ? Response::allow() : Response::deny();
        }
        
        return null;
    }
    
    public function check($ability, ...$arguments)
    {
        return $this->authorize($ability, ...$arguments)->allowed();
    }
    
    public function denies($ability, ...$arguments)
    {
        return !$this->check($ability, ...$arguments);
    }
    
    public function authorize($ability, ...$arguments)
    {
        $result = $this->raw($ability, ...$arguments);
        
        if ($result instanceof Response) {
            return $result;
        }
        
        return $result ? Response::allow() : Response::deny();
    }
    
    public function raw($ability, ...$arguments)
    {
        foreach ($this->beforeCallbacks as $callback) {
            $result = $callback($this->user, $ability, ...$arguments);
            if (!is_null($result)) {
                return $result;
            }
        }
        
        $model = isset($arguments[0]) ? $arguments[0] : null;
        if ($model) {
            $policyClass = $this->getPolicyFor($model);
            if ($policyClass) {
                $result = $this->callPolicyMethod($policyClass, $ability, $this->user, ...$arguments);
                if (!is_null($result)) {
                    return $result;
                }
            }
        }
        
        if (isset($this->abilities[$ability])) {
            return call_user_func($this->abilities[$ability], $this->user, ...$arguments);
        }
        
        foreach ($this->afterCallbacks as $callback) {
            $result = $callback($this->user, $ability, ...$arguments);
            if (!is_null($result)) {
                return $result;
            }
        }
        
        return false;
    }
    
    public function inspect($ability, ...$arguments)
    {
        $result = $this->raw($ability, ...$arguments);
        
        if ($result instanceof Response) {
            return [
                'ability' => $ability,
                'allowed' => $result->allowed(),
                'message' => $result->message(),
                'code' => $result->code(),
            ];
        }
        
        return [
            'ability' => $ability,
            'allowed' => (bool) $result,
            'message' => null,
            'code' => null,
        ];
    }
}