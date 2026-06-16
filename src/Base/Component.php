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
}