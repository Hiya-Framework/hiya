<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Queue\QueueDriverInterface
 * @since 1.0
 */

namespace Hiya\Queue;

/**
 * Queue Driver Interface
 */

interface QueueDriverInterface
{
    /**
     * Push job to queue
     */
    public function push($job, $data = [], $delay = 0, $priority = 'medium');
    
    /**
     * Push high priority job
     */
    public function pushHigh($job, $data = [], $delay = 0);
    
    /**
     * Push low priority job
     */
    public function pushLow($job, $data = [], $delay = 0);
    
    /**
     * Push delayed job
     */
    public function later($delay, $job, $data = []);
    
    /**
     * Pop next job from queue
     */
    public function pop($queue = null);
    
    /**
     * Mark job as completed
     */
    public function complete($jobId);
    
    /**
     * Mark job as failed
     */
    public function fail($jobId, $errorMessage = null);
    
    /**
     * Release job back to queue
     */
    public function release($jobId, $delay = 0);
    
    /**
     * Retry failed jobs
     */
    public function retryFailed($queue = null, $limit = 100);
    
    /**
     * Get queue statistics
     */
    public function stats($queue = null);
    
    /**
     * Delete job
     */
    public function delete($jobId);
    
    /**
     * Clear all jobs by status
     */
    public function clear($status, $queue = null, $olderThan = null);
}