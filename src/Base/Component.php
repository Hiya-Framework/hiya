<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Base\Component
 * @since 1.0
 */

namespace Hiya\Base;

class Component extends \CApplicationComponent
{
    public function __construct()
    {
        // this auto load when component intialize
        $this->init();
    }

    public function init()
    {
        parent::init();
    }
}
