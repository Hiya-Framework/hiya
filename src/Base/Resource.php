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

class Resource
{
    protected $data;
    
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function toArray(): array
    {
        return get_object_vars($this->data);
    }
    
    public static function collection($data): array
    {
        return array_map(function($item) {
            return (new static($item))->toArray();
        }, $data);
    }
}