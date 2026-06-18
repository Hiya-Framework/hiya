<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Component
 * @since 1.0
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