<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Component;

class Queue extends \CApplicationComponent
{
    public $default = 'file';
    public $connections = [];
    
    /**
     * @var Queue
     */
    protected $_queue;
    
    public function init()
    {
        parent::init();
        
        $config = isset($this->connections[$this->default]) 
            ? $this->connections[$this->default] 
            : [];
        
        $this->_queue = new Queue($this->default, $config);
    }
    
    /**
     * Get queue instance
     * 
     * @return Queue
     */
    public function getQueue()
    {
        return $this->_queue;
    }
    
    /**
     * Magic method to forward calls to queue instance
     * 
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->_queue, $name)) {
            return call_user_func_array([$this->_queue, $name], $arguments);
        }
        
        throw new \CException("Method '{$name}' not found in Queue component");
    }
}