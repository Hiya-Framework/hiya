<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Web;

use CWebModule;

class Module extends CWebModule
{
    public $layout = "//layout/main";

    public function init()
    {
        parent::init();
    }
}