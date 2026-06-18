<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Base
 * @since 1.0
 * 
 * Resources
 */

namespace Hiya\Base;

class DTO
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
    
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}