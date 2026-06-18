<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Queue\Queue
 * @since 1.0
 */

namespace Hiya\Queue;

use Hiya;
use ReflectionClass;
use ReflectionProperty;

/**
 * Queue System - Flexible with multiple drivers
 */
class Queue
{
    /**
     * @var QueueDriverInterface Driver instance
     */
    protected $driver = null;
    
    /**
     * @var string Default queue name
     */
    protected $defaultQueue = 'default';
    
    /**
     * @var array Driver configurations
     */
    protected $config = [];
    
    /**
     * Constructor
     * 
     * @param string $driver Driver name (file, database, redis, memory)
     * @param array $config Driver configuration
     */
    public function __construct($driver = 'file', $config = [])
    {
        $this->config = $config;
        $this->driver = $this->createDriver($driver);
    }
    
    /**
     * Create driver instance
     * 
     * @param string $driver Driver name
     * @return QueueDriverInterface
     * @throws \Exception
     */
    protected function createDriver($driver)
    {
        // Build full class name with namespace
        $driverClass = 'Hiya\\Queue\\Driver\\Queue' . ucfirst($driver) . 'Driver';
        
        // Check if class exists
        if (!class_exists($driverClass)) {
            // Alternative path for driver file
            $driverFile = __DIR__ . '/Driver/Queue' . ucfirst($driver) . 'Driver.php';
            
            if (file_exists($driverFile)) {
                require_once $driverFile;
            } else {
                // Try alternative paths
                $altPaths = [
                    __DIR__ . '/QueueDrivers/Queue' . ucfirst($driver) . 'Driver.php',
                    __DIR__ . '/drivers/Queue' . ucfirst($driver) . 'Driver.php',
                ];
                
                $found = false;
                foreach ($altPaths as $path) {
                    if (file_exists($path)) {
                        require_once $path;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    throw new \Exception("Queue driver '{$driver}' not found. Tried: " . implode(', ', $altPaths));
                }
            }
            
            // Check again after loading
            if (!class_exists($driverClass)) {
                throw new \Exception("Queue driver class '{$driverClass}' not found after loading file");
            }
        }
        
        return new $driverClass($this->config);
    }
    
    /**
     * Set default queue name
     */
    public function setQueue($queueName)
    {
        $this->defaultQueue = $queueName;
        return $this;
    }
    
    /**
     * Push job to queue
     */
    public function push($job, $data = [], $delay = 0, $priority = 'medium', $queue = null)
    {
        $queue = $queue ?: $this->defaultQueue;
        return $this->driver->push($job, $data, $delay, $priority, $queue);
    }
    
    /**
     * Push high priority job
     */
    public function pushHigh($job, $data = [], $delay = 0, $queue = null)
    {
        return $this->push($job, $data, $delay, 'high', $queue);
    }
    
    /**
     * Push low priority job
     */
    public function pushLow($job, $data = [], $delay = 0, $queue = null)
    {
        return $this->push($job, $data, $delay, 'low', $queue);
    }
    
    /**
     * Push delayed job
     */
    public function later($delay, $job, $data = [], $queue = null)
    {
        return $this->push($job, $data, $delay, 'medium', $queue);
    }
    
    /**
     * Push job to specific queue
     */
    public function onQueue($queue, $job, $data = [], $delay = 0, $priority = 'medium')
    {
        return $this->push($job, $data, $delay, $priority, $queue);
    }
    
    /**
     * Pop next job from queue
     */
    public function pop($queue = null)
    {
        $queue = $queue ?: $this->defaultQueue;
        return $this->driver->pop($queue);
    }
    
    /**
     * Mark job as completed
     */
    public function complete($jobId, $queue = null)
    {
        return $this->driver->complete($jobId, $queue);
    }
    
    /**
     * Mark job as failed
     */
    public function fail($jobId, $errorMessage = null, $queue = null)
    {
        return $this->driver->fail($jobId, $errorMessage, $queue);
    }
    
    /**
     * Release job back to queue
     */
    public function release($jobId, $delay = 0, $queue = null)
    {
        return $this->driver->release($jobId, $delay, $queue);
    }
    
    /**
     * Retry failed jobs
     */
    public function retryFailed($queue = null, $limit = 100)
    {
        return $this->driver->retryFailed($queue, $limit);
    }
    
    /**
     * Get queue statistics
     */
    public function stats($queue = null)
    {
        return $this->driver->stats($queue);
    }
    
    /**
     * Delete job
     */
    public function delete($jobId, $queue = null)
    {
        return $this->driver->delete($jobId, $queue);
    }
    
    /**
     * Clear jobs by status
     */
    public function clear($status, $queue = null, $olderThan = null)
    {
        return $this->driver->clear($status, $queue, $olderThan);
    }
    
    public function actionReset($queue = 'default')
    {
        // Get base directory from config via queue component
        $queueComponent = Hiya::app()->queue;
        
        // Try to get the driver and its base directory
        $reflection = new ReflectionClass($queueComponent);
        $property = $reflection->getProperty('_queue');
        $property->setAccessible(true);
        $queueInstance = $property->getValue($queueComponent);
        
        $driverProperty = new ReflectionProperty($queueInstance, 'driver');
        $driverProperty->setAccessible(true);
        $driver = $driverProperty->getValue($queueInstance);
        
        if (method_exists($driver, 'getBaseDir')) {
            $baseDir = $driver->getBaseDir();
        } else {
            // Fallback to default path
            $runtimePath = Hiya::getPathOfAlias('application.runtime');
            $baseDir = $runtimePath . '/queue';
        }
        
        $queueDir = $baseDir . '/' . $queue;
        
        echo "Resetting queue: {$queue}\n";
        echo "Base directory: {$queueDir}\n\n";
        
        $statuses = ['pending', 'processing', 'completed', 'failed', 'delayed'];
        $total = 0;
        
        foreach ($statuses as $status) {
            $dir = $queueDir . '/' . $status;
            if (is_dir($dir)) {
                $files = glob($dir . '/*.job');
                $count = count($files);
                foreach ($files as $file) {
                    unlink($file);
                }
                echo "  {$status}: deleted {$count} files\n";
                $total += $count;
            } else {
                echo "  {$status}: directory not found\n";
            }
        }
        
        echo "\nTotal deleted: {$total} jobs\n";
    }

    /**
     * Process queue (run worker)
     * 
     * @param string $queue Queue name
     * @param int $sleep Sleep time when no jobs
     * @param bool $once Run once only
     * @param int $maxJobs Maximum jobs to process
     * @return int Number of jobs processed
     */
    public function work($queue = null, $sleep = 3, $once = false, $maxJobs = 0)
    {
        $queue = $queue ?: $this->defaultQueue;
        $processed = 0;
        
        while (true) {
            try {
                $job = $this->pop($queue);
                
                if ($job) {
                    echo "[" . date('Y-m-d H:i:s') . "] Processing job: " . $job->getId() . "\n";
                    
                    try {
                        $job->fire();
                        echo "[" . date('Y-m-d H:i:s') . "] Job completed: " . $job->getId() . "\n";
                        $processed++;
                    } catch (\Exception $e) {
                        echo "[" . date('Y-m-d H:i:s') . "] Job failed: " . $job->getId() . " - " . $e->getMessage() . "\n";
                    }
                    
                    if ($once) {
                        break;
                    }
                    
                    if ($maxJobs > 0 && $processed >= $maxJobs) {
                        break;
                    }
                } else {
                    if ($once) {
                        break;
                    }
                    echo "[" . date('Y-m-d H:i:s') . "] No jobs, sleeping for {$sleep}s...\n";
                    sleep($sleep);
                }
            } catch (\Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . "\n";
                sleep(1);
            }
        }
        
        return $processed;
    }
}