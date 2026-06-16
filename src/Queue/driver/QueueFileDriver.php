<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Queue\QueueFileDriver
 * @since 1.0
 */

namespace Hiya\Queue\Driver;

use Hiya\Queue\QueueDriverInterface;
use Hiya\Queue\QueueJob;

/**
 * File-based Queue Driver
 */

class QueueFileDriver implements QueueDriverInterface
{
    /**
     * @var string Base directory
     */
    protected $baseDir;
    
    /**
     * @var array Subdirectories
     */
    protected $subDirs = ['pending', 'processing', 'completed', 'failed', 'delayed'];
    
    /**
     * Constructor
     * 
     * @param array $config Configuration
     */
    public function __construct($config = [])
    {
        // Gunakan path dari config jika ada
        if (isset($config['path'])) {
            $this->baseDir = rtrim($config['path'], '/');
        } else {
            // Default: gunakan application.runtime/queue
            if (function_exists('Hiya::getPathOfAlias')) {
                $runtimePath = Hiya::getPathOfAlias('application.runtime');
                if (!$runtimePath) {
                    throw new \CException('Application runtime path not found. Please set "path" in queue configuration.');
                }
                $this->baseDir = $runtimePath . '/queue';
            } else {
                throw new \CException('Queue driver requires "path" configuration or Hiya framework runtime path.');
            }
        }
        
        // Pastikan base directory valid
        if (empty($this->baseDir)) {
            throw new \CException('Queue base directory cannot be empty. Please configure "path" in queue settings.');
        }
        
        // Buat direktori jika belum ada
        if (!is_dir($this->baseDir)) {
            if (!mkdir($this->baseDir, 0777, true)) {
                throw new \CException('Cannot create queue directory: ' . $this->baseDir);
            }
        }
        
        $this->initDirectories();
    }

    public function getBaseDir()
    {
        return $this->baseDir;
    }
    
    /**
     * Initialize directories
     */
    protected function initDirectories()
    {
        foreach ($this->subDirs as $dir) {
            $path = $this->baseDir . '/default/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }
    
    /**
     * Get directory path
     */
    protected function getDir($status, $queue = 'default')
    {
        $path = $this->baseDir . '/' . $queue . '/' . $status;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }
    
    /**
     * Generate job ID
     */
    protected function generateJobId()
    {
        return uniqid() . '_' . time() . '_' . mt_rand(1000, 9999);
    }
    
    /**
     * Get priority prefix
     */
    protected function getPriorityPrefix($priority)
    {
        $prefixes = ['high' => '001', 'medium' => '002', 'low' => '003'];
        return $prefixes[$priority] ?? '002';
    }
    
    /**
     * {@inheritdoc}
     */
    public function push($job, $data = [], $delay = 0, $priority = 'medium', $queue = 'default')
    {
        $jobId = $this->generateJobId();
        
        $payload = json_encode([
            'job' => $job,
            'data' => $data,
            'max_attempts' => 3
        ]);
        
        $jobData = [
            'id' => $jobId,
            'queue' => $queue,
            'payload' => $payload,
            'priority' => $priority,
            'attempts' => 0,
            'created_at' => time(),
            'available_at' => time() + $delay,
            'status' => $delay > 0 ? 'delayed' : 'pending'
        ];
        
        $status = $delay > 0 ? 'delayed' : 'pending';
        $dir = $this->getDir($status, $queue);
        $fileName = $dir . '/' . $this->getPriorityPrefix($priority) . '_' . $jobId . '.job';
        
        file_put_contents($fileName, json_encode($jobData, JSON_PRETTY_PRINT));
        
        if ($status === 'pending') {
            $this->sortPendingFiles($queue);
        }
        
        return $jobId;
    }
    
    /**
     * Sort pending files by priority
     */
    protected function sortPendingFiles($queue)
    {
        $pendingDir = $this->getDir('pending', $queue);
        $files = glob($pendingDir . '/*.job');
        sort($files);
    }
    
    /**
     * {@inheritdoc}
     */
    public function pushHigh($job, $data = [], $delay = 0, $queue = 'default')
    {
        return $this->push($job, $data, $delay, 'high', $queue);
    }
    
    /**
     * {@inheritdoc}
     */
    public function pushLow($job, $data = [], $delay = 0, $queue = 'default')
    {
        return $this->push($job, $data, $delay, 'low', $queue);
    }
    
    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = [], $queue = 'default')
    {
        return $this->push($job, $data, $delay, 'medium', $queue);
    }
    
    /**
     * Move ready delayed jobs to pending
     */
    protected function moveReadyDelayedJobs($queue)
    {
        $delayedDir = $this->getDir('delayed', $queue);
        
        if (!is_dir($delayedDir)) return;
        
        $files = glob($delayedDir . '/*.job');
        $now = time();
        
        foreach ($files as $file) {
            $jobData = json_decode(file_get_contents($file), true);
            if ($jobData && isset($jobData['available_at']) && $jobData['available_at'] <= $now) {
                $pendingDir = $this->getDir('pending', $queue);
                $newFile = $pendingDir . '/' . basename($file);
                $jobData['status'] = 'pending';
                file_put_contents($newFile, json_encode($jobData, JSON_PRETTY_PRINT));
                unlink($file);
            }
        }
        
        $this->sortPendingFiles($queue);
    }
    
    /**
     * {@inheritdoc}
     */
    public function pop($queue = 'default')
    {
        $this->moveReadyDelayedJobs($queue);
        
        $pendingDir = $this->getDir('pending', $queue);
        if (!is_dir($pendingDir)) return null;
        
        $files = glob($pendingDir . '/*.job');
        if (empty($files)) return null;
        
        // Sort by created time (oldest first)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        $jobFile = $files[0];
        $content = file_get_contents($jobFile);
        $jobData = json_decode($content, true);
        
        if (!$jobData || !is_array($jobData)) {
            // Invalid job file, delete it
            unlink($jobFile);
            echo "  Warning: Invalid job file deleted: " . basename($jobFile) . "\n";
            return null;
        }
        
        // Ensure payload is properly set
        if (!isset($jobData['payload'])) {
            echo "  Warning: Job missing payload: " . basename($jobFile) . "\n";
            unlink($jobFile);
            return null;
        }
        
        $processingDir = $this->getDir('processing', $queue);
        $newFile = $processingDir . '/' . basename($jobFile);
        rename($jobFile, $newFile);
        
        return new QueueJob($jobData, $this, $newFile, $queue);
    }
    
    /**
     * {@inheritdoc}
     */
    public function complete($jobId, $queue = null)
    {
        $jobData = $this->findJob($jobId);
        if (!$jobData) {
            return false;
        }
        
        $completedDir = $this->getDir('completed', $jobData['queue']);
        $newFile = $completedDir . '/' . basename($jobData['file']);
        
        if (file_exists($jobData['file'])) {
            $jobInfo = json_decode(file_get_contents($jobData['file']), true);
            $jobInfo['status'] = 'completed';
            $jobInfo['completed_at'] = time();
            file_put_contents($newFile, json_encode($jobInfo, JSON_PRETTY_PRINT));
            unlink($jobData['file']);
            return true;
        }
        
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function fail($jobId, $errorMessage = null, $queue = null)
    {
        $jobData = $this->findJob($jobId);
        if (!$jobData) {
            return false;
        }
        
        $failedDir = $this->getDir('failed', $jobData['queue']);
        $newFile = $failedDir . '/' . basename($jobData['file']);
        
        if (file_exists($jobData['file'])) {
            $jobInfo = json_decode(file_get_contents($jobData['file']), true);
            $jobInfo['status'] = 'failed';
            $jobInfo['failed_at'] = time();
            $jobInfo['error_message'] = $errorMessage;
            file_put_contents($newFile, json_encode($jobInfo, JSON_PRETTY_PRINT));
            unlink($jobData['file']);
            return true;
        }
        
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function release($jobId, $delay = 0, $queue = null)
    {
        $jobData = $this->findJob($jobId);
        if (!$jobData) {
            return false;
        }
        
        $status = $delay > 0 ? 'delayed' : 'pending';
        $targetDir = $this->getDir($status, $jobData['queue']);
        $newFile = $targetDir . '/' . basename($jobData['file']);
        
        if (file_exists($jobData['file'])) {
            $jobInfo = json_decode(file_get_contents($jobData['file']), true);
            $jobInfo['status'] = $status;
            $jobInfo['available_at'] = time() + $delay;
            file_put_contents($newFile, json_encode($jobInfo, JSON_PRETTY_PRINT));
            unlink($jobData['file']);
            
            if ($status === 'pending') {
                $this->sortPendingFiles($jobData['queue']);
            }
            
            return true;
        }
        
        return false;
    }
        

    /**
     * Find job by ID
     */
    protected function findJob($jobId)
    {
        $queues = $this->getAllQueues();
        
        foreach ($queues as $queue) {
            foreach ($this->subDirs as $status) {
                $dir = $this->getDir($status, $queue);
                // Pattern yang lebih fleksibel
                $patterns = [
                    $dir . '/' . $jobId . '.job',
                    $dir . '/*_' . $jobId . '.job',
                    $dir . '/' . $jobId . '_*.job',
                ];
                
                foreach ($patterns as $pattern) {
                    $files = glob($pattern);
                    if (!empty($files)) {
                        return [
                            'file' => $files[0],
                            'data' => json_decode(file_get_contents($files[0]), true),
                            'queue' => $queue
                        ];
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function retryFailed($queue = null, $limit = 100)
    {
        $queues = $queue ? [$queue] : $this->getAllQueues();
        $count = 0;
        
        foreach ($queues as $q) {
            $failedDir = $this->getDir('failed', $q);
            
            if (!is_dir($failedDir)) continue;
            
            $files = glob($failedDir . '/*.job');
            
            foreach ($files as $file) {
                if ($count >= $limit) break 2;
                
                $jobData = json_decode(file_get_contents($file), true);
                if ($jobData) {
                    $jobData['status'] = 'pending';
                    $jobData['attempts'] = 0;
                    $jobData['available_at'] = time();
                    unset($jobData['failed_at']);
                    unset($jobData['error_message']);
                    
                    $pendingDir = $this->getDir('pending', $q);
                    $newFile = $pendingDir . '/' . basename($file);
                    file_put_contents($newFile, json_encode($jobData, JSON_PRETTY_PRINT));
                    unlink($file);
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * {@inheritdoc}
     */
    public function stats($queue = null)
    {
        $queues = $queue ? [$queue] : $this->getAllQueues();
        $stats = [];
        
        foreach ($queues as $q) {
            $stats[$q] = [];
            foreach ($this->subDirs as $status) {
                $dir = $this->baseDir . '/' . $q . '/' . $status;
                
                if (is_dir($dir)) {
                    $files = glob($dir . '/*.job');
                    $stats[$q][$status] = count($files);
                } else {
                    $stats[$q][$status] = 0;
                }
            }
        }
        
        if ($queue) {
            Yii::log("DEBUG: Using base directory: {$this->baseDir}", CLogger::LEVEL_INFO, 'queue');
        }
        
        return $queue ? ($stats[$queue] ?? []) : $stats;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($jobId, $queue = null)
    {
        $jobData = $this->findJob($jobId);
        if ($jobData && file_exists($jobData['file'])) {
            unlink($jobData['file']);
            return true;
        }
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear($status, $queue = null, $olderThan = null)
    {
        $queues = $queue ? [$queue] : $this->getAllQueues();
        $count = 0;
        
        foreach ($queues as $q) {
            $dir = $this->getDir($status, $q);
            
            if (!is_dir($dir)) continue;
            
            // Use glob pattern to find all job files
            $files = glob($dir . '/*.job');
            
            if (empty($files)) {
                echo "  No files found in {$dir}\n";
                continue;
            }
            
            Yii::log("Found " . count($files) . " files in {$dir}", CLogger::LEVEL_INFO, 'queue');

            foreach ($files as $file) {
                $shouldDelete = true;
                
                if ($olderThan) {
                    $cutoff = time() - ($olderThan * 86400);
                    $jobData = json_decode(file_get_contents($file), true);
                    $timestamp = $jobData['created_at'] ?? 0;
                    if ($status === 'completed' && isset($jobData['completed_at'])) {
                        $timestamp = $jobData['completed_at'];
                    }
                    if ($status === 'failed' && isset($jobData['failed_at'])) {
                        $timestamp = $jobData['failed_at'];
                    }
                    $shouldDelete = $timestamp < $cutoff;
                }
                
                if ($shouldDelete) {
                    if (unlink($file)) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }

    /**
     * Get all queue names
     */
    protected function getAllQueues()
    {
        $queues = ['default'];
        
        if (is_dir($this->baseDir)) {
            $items = scandir($this->baseDir);
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($this->baseDir . '/' . $item)) {
                    if (!in_array($item, $queues)) {
                        $queues[] = $item;
                    }
                }
            }
        }
        
        return $queues;
    }
}