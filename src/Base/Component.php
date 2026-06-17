<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Base\Component
 * @since 1.0
 */

namespace Hiya\Base;

/**
 * Class Component
 * 
 * Extends Yii1 CComponent to provide:
 * - Event handling (onBeforeSave, onAfterSave, etc.)
 * - Behavior attachment (behaviors)
 * - Magic getter/setter (__get, __set)
 * - Property access (getX, setX)
 * 
 * @example
 * class MyClass extends Component
 * {
 *     private $_name;
 *     
 *     public function getName() { return $this->_name; }
 *     public function setName($value) { $this->_name = $value; }
 * }
 * 
 * $obj = new MyClass();
 * $obj->name = 'John'; // Calls setName()
 */
class Component extends \CComponent
{
    /**
     * Constructor
     */
    public function __construct($config = [])
    {
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }
        $this->init();
    }
    
    /**
     * Initialize component
     * Override in child class
     */
    public function init()
    {
        parent::init();
    }
    
    /**
     * Get component ID
     * 
     * @return string
     */
    public function getId()
    {
        return spl_object_hash($this);
    }
    
    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray()
    {
        $reflection = new \ReflectionClass($this);
        $properties = [];
        
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $name = $property->getName();
            if (strpos($name, '_') !== 0) {
                $properties[$name] = $property->getValue($this);
            }
        }
        
        return $properties;
    }

    /**
     * Magic getter untuk properties
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return parent::__get($name);
    }

    /**
     * Magic setter untuk properties
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }
        parent::__set($name, $value);
    }

    /**
     * Trigger event with data
     * 
     * @param string $eventName
     * @param array $params
     * @return $this
     */
    public function trigger($eventName, $params = [])
    {
        $this->raiseEvent($eventName, new \CEvent($this, $params));
        return $this;
    }
}