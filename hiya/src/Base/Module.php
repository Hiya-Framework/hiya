<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Base;

use CModule;

class Module extends \CModule
{
    /**
     * @var array Module configuration
     */
    protected $_config = [];
    
    /**
     * Constructor
     * @param string $id Module ID
     * @param CModule|null $parent Parent module
     * @param array $config Configuration
     */
    public function __construct($id, $parent = null, $config = [])
    {
        $this->_config = $config;
        
        parent::__construct($id, $parent);
        
        foreach ($this->_config as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        $this->init();
    }
    
    /**
     * Initialize module
     */
    public function init()
    {
        parent::init();
    }
}