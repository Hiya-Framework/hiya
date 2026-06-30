<?php
/**
 * Hiya Framework - Session Component
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @license BSD-3-Clause
 */

namespace Hiya\Component;

class Session extends \CHttpSession
{
    /**
     * Check if session key exists
     * Alias for contains()
     * 
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->contains($key);
    }
    
    /**
     * Set session state
     * Alias for add()
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setState($key, $value)
    {
        $this->add($key, $value);
        return $this;
    }
    
    /**
     * Get session state
     * Alias for get()
     * 
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getState($key, $defaultValue = null)
    {
        return $this->get($key, $defaultValue);
    }

    /**
     * Set session state
     * Alias for setState()
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $defaultValue = null)
    {
        return $this->setState($key, $defaultValue);
    }
}