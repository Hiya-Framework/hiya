<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Web;

use CActiveForm;

class ActiveForm extends \CActiveForm
{
    public function textField($model, $attribute, $htmlOptions = array())
    {
        $this->setFieldName($model, $attribute, $htmlOptions);
        return parent::textField($model, $attribute, $htmlOptions);
    }
    
    public function passwordField($model, $attribute, $htmlOptions = array())
    {
        $this->setFieldName($model, $attribute, $htmlOptions);
        return parent::passwordField($model, $attribute, $htmlOptions);
    }
    
    public function checkBox($model, $attribute, $htmlOptions = array())
    {
        $this->setFieldName($model, $attribute, $htmlOptions);
        return parent::checkBox($model, $attribute, $htmlOptions);
    }
    
    public function dropDownList($model, $attribute, $data, $htmlOptions = array())
    {
        $this->setFieldName($model, $attribute, $htmlOptions);
        return parent::dropDownList($model, $attribute, $data, $htmlOptions);
    }
    
    public function textArea($model, $attribute, $htmlOptions = array())
    {
        $this->setFieldName($model, $attribute, $htmlOptions);
        return parent::textArea($model, $attribute, $htmlOptions);
    }
    
    public function labelEx($model, $attribute, $htmlOptions = array())
    {
        // Set 'for' attribute if not specified
        if (!isset($htmlOptions['for'])) {
            $formName = $this->getFormName($model);
            $htmlOptions['for'] = $formName . '_' . $attribute;
        }
        
        return parent::labelEx($model, $attribute, $htmlOptions);
    }
    
    public function error($model, $attribute, $htmlOptions = array(), $enableAjaxValidation = true, $enableClientValidation = true)
    {
        // Ensure error id matches field name
        $formName = $this->getFormName($model);
        if (!isset($htmlOptions['id'])) {
            $htmlOptions['id'] = $formName . '_' . $attribute . '_error';
        }
        
        return parent::error($model, $attribute, $htmlOptions, $enableAjaxValidation, $enableClientValidation);
    }
    
    /**
     * Set field name and id from model formName
     */
    protected function setFieldName($model, $attribute, &$htmlOptions)
    {
        $formName = $this->getFormName($model);
        
        // Set name if not explicitly defined
        if (!isset($htmlOptions['name'])) {
            $htmlOptions['name'] = $formName . '[' . $attribute . ']';
        }
        
        // Set id if not explicitly defined
        if (!isset($htmlOptions['id'])) {
            $htmlOptions['id'] = $formName . '_' . $attribute;
        }
    }
    
    /**
     * Get form name from model
     */
    protected function getFormName($model)
    {
        if (method_exists($model, 'formName')) {
            return $model->formName();
        }
        
        // Fallback: get class name without namespace
        $className = get_class($model);
        $parts = explode('\\', $className);
        return end($parts);
    }
}