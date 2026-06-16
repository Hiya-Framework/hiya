<?php
namespace Hiya\Auth;

abstract class Policy
{
    public function before($user, $ability)
    {
        return null;
    }
    
    public function after($user, $ability, $result)
    {
        return null;
    }
}