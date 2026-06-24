<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Web;

class FormModel extends \CFormModel
{
    private static $_formNames = [];
    private static $_formIds = [];
    
    /**
     * Get form name without namespace (auto-detect)
     * Override this method to customize form name
     * 
     * @return string
     */
    public function formName()
    {
        $className = get_class($this);
        
        // Check cache
        if (!isset(self::$_formNames[$className])) {
            // Get short class name without namespace
            $parts = explode('\\', $className);
            $shortName = end($parts);
            
            // Support untuk class dengan suffix 'Form'
            // Misal: LoginForm -> Login, UserForm -> User
            // Tapi tetap simpan full short name
            self::$_formNames[$className] = $shortName;
        }
        
        return self::$_formNames[$className];
    }
    
    /**
     * Get form name with namespace (original Yii1 style)
     * 
     * @return string
     */
    public function fullFormName()
    {
        $className = get_class($this);
        return str_replace('\\', '_', $className);
    }
    
    /**
     * Get form ID (auto-generated)
     * 
     * @return string
     */
    public function formId()
    {
        $className = get_class($this);
        
        if (!isset(self::$_formIds[$className])) {
            $formName = $this->formName();
            self::$_formIds[$className] = strtolower($formName) . '-form';
        }
        
        return self::$_formIds[$className];
    }
    
    /**
     * Get form name with prefix (for module)
     * 
     * @param string $prefix
     * @return string
     */
    public function formNameWithPrefix($prefix)
    {
        return $prefix . ucfirst($this->formName());
    }
    
    /**
     * Override CModel::getAttributeLabel
     * Auto-generate labels if not defined
     */
    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();
        if (isset($labels[$attribute])) {
            return $labels[$attribute];
        }
        
        // Auto-generate label from attribute name
        return ucwords(str_replace('_', ' ', $attribute));
    }
}