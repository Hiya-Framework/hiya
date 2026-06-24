<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Base;

use Hiya\Module;
use Hiya\WebApplication;

class Application extends Module
{
    public function __construct($config = [])
    {
        $id = $config['id'] ?? 'app';
        $parent = null;
        
        parent::__construct($id, $parent, $config);
        
        $this->init();
    }

    private function createWebApplication($config=null)
	{
		return new WebApplication($config);
	}

    public function run($config){
        $this->createWebApplication($config);
    }
}