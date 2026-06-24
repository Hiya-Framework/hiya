<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Component;

interface IAuth
{
    /**
     * @return array Decoded/Authorized user data
     * @throws \CHttpException
     */
    public function authorize();
}