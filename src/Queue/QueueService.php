<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Queue\QueueService
 * @since 1.0
 */

namespace Hiya\Queue;

/**
 * Queue Service - Singleton manager for queue instances
 */

class QueueService
{
    /**
     * @var QueueService Singleton instance
     */
    protected static $instance = null;
    
    /**
     * @var array Queue instances
     */
    protected $queues = [];
    
    /**
     * @var string Default driver
     */
    protected $defaultDriver = 'file';
    
    /**
     * @var array Driver configurations
     */
    protected $configs = [];
    
    /**
     * Private constructor (singleton)
     */
    private function __construct()
    {
        // Load configuration from Yii params
        $config = Hiya::app()->params['queue'] ?? [];
        $this->defaultDriver = isset($config['default']) ? $config['default'] : 'file';
        $this->configs = isset($config['connections']) ? $config['connections'] : [];
    }
    
    /**
     * Get singleton instance
     * 
     * @return QueueService
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get queue connection
     * 
     * @param string $driver Driver name (null for default)
     * @return Queue
     */
    public function connection($driver = null)
    {
        $driver = $driver ?: $this->defaultDriver;
        
        if (!isset($this->queues[$driver])) {
            $config = isset($this->configs[$driver]) ? $this->configs[$driver] : [];
            $this->queues[$driver] = new Queue($driver, $config);
        }
        
        return $this->queues[$driver];
    }
    
    /**
     * Magic method to call queue methods on default connection
     */
    public static function __callStatic($name, $arguments)
    {
        $instance = self::getInstance();
        $queue = $instance->connection();
        return call_user_func_array([$queue, $name], $arguments);
    }
}