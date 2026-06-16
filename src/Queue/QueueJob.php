<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Queue\QueueJob
 * @since 1.0
 */

namespace Hiya\Queue;

class QueueJob
{
    protected $job;
    protected $driver;
    protected $filePath;
    protected $queue;
    
    public function __construct($job, $driver, $filePath, $queue)
    {
        $this->job = $job;
        $this->driver = $driver;
        $this->filePath = $filePath;
        $this->queue = $queue;
    }
    
    public function getId()
    {
        return isset($this->job['id']) ? $this->job['id'] : 'unknown';
    }
    
    public function getQueue()
    {
        return $this->queue;
    }
    
    public function getData()
    {
        $payload = $this->getPayload();
        return isset($payload['data']) ? $payload['data'] : [];
    }
    
    public function getAttempts()
    {
        return isset($this->job['attempts']) ? (int)$this->job['attempts'] : 0;
    }
    
    protected function getPayload()
    {
        // Decode payload if it's a string
        if (is_string($this->job['payload'])) {
            $decoded = json_decode($this->job['payload'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
            return [];
        }
        
        // Return as is if already array
        if (is_array($this->job['payload'])) {
            return $this->job['payload'];
        }
        
        return [];
    }
    
    public function fire()
    {
        $payload = $this->getPayload();
        
        echo "  Debug - Payload: " . json_encode($payload) . "\n";
        
        if (empty($payload) || !isset($payload['job'])) {
            echo "  Error: Invalid job payload\n";
            // Move to failed
            $this->driver->fail($this->getId(), "Invalid job payload", $this->queue);
            return false;
        }
        
        $job = $payload['job'];
        $data = isset($payload['data']) ? $payload['data'] : [];
        $attempts = $this->getAttempts() + 1;
        $maxAttempts = isset($payload['max_attempts']) ? (int)$payload['max_attempts'] : 3;
        
        echo "  Debug - Job type: " . gettype($job) . "\n";
        echo "  Debug - Job value: " . (is_scalar($job) ? $job : json_encode($job)) . "\n";
        echo "  Debug - Attempts: {$attempts}/{$maxAttempts}\n";
        
        try {
            $success = false;
            
            // Check if job is a closure/callable
            if (is_callable($job)) {
                echo "  Executing callable job\n";
                call_user_func($job, $data);
                $success = true;
            } 
            // Check if job is a class name (string)
            elseif (is_string($job) && class_exists($job)) {
                echo "  Instantiating class: {$job}\n";
                $instance = new $job();
                if (method_exists($instance, 'handle')) {
                    echo "  Calling handle() method\n";
                    $instance->handle($data);
                    $success = true;
                } else {
                    echo "  Error: Class {$job} does not have handle method\n";
                }
            } 
            else {
                echo "  Error: Job is not callable. Type: " . gettype($job) . "\n";
            }
            
            if ($success) {
                // Mark as completed
                $this->driver->complete($this->getId(), $this->queue);
                echo "  Job completed successfully\n";
                return true;
            } else {
                throw new \Exception("Job execution failed");
            }
            
        } catch (\Exception $e) {
            echo "  Exception: " . $e->getMessage() . "\n";
            
            // Update attempts
            $this->updateAttempts($attempts);
            
            // Check max attempts
            if ($attempts >= $maxAttempts) {
                echo "  Max attempts reached, moving to failed\n";
                $this->driver->fail($this->getId(), $e->getMessage(), $this->queue);
            } else {
                // Exponential backoff: 5s, 25s, 125s
                $delay = pow(5, $attempts);
                echo "  Retrying in {$delay} seconds (attempt {$attempts}/{$maxAttempts})\n";
                $this->driver->release($this->getId(), $delay, $this->queue);
            }
            
            return false;
        }
    }
    
    
    protected function updateAttempts($attempts)
    {
        if (file_exists($this->filePath)) {
            $jobData = json_decode(file_get_contents($this->filePath), true);
            if (is_array($jobData)) {
                $jobData['attempts'] = $attempts;
                file_put_contents($this->filePath, json_encode($jobData));
                $this->job['attempts'] = $attempts;
            }
        }
    }
}