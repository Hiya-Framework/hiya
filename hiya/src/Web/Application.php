<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Web;

class Application extends \CWebApplication
{
    public function init()
    {

        // By default Hiya application will use App as base namesepace
        // example location: protected
        // we can register it from config/main.php
        \Yii::setPathOfAlias('App', $this->getBasePath());

        parent::init();
    }
}