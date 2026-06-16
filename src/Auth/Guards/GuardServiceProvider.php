<?php
namespace Hiya\Auth\Guards;

use Hiya;

/**
 * Guard Service Provider - Register guard as Hiya component
 */
class GuardServiceProvider
{
    /**
     * Register guard as Hiya component
     * 
     * @param string $name Component name (default: 'auth')
     * @param callable|null $userProvider User provider callback
     */
    public static function register($name = 'auth', callable $userProvider = null)
    {
        Hiya::setComponent($name, [
            'class' => 'Hiya\\Components\\GuardComponent',
            'userProvider' => $userProvider,
        ]);
    }
}