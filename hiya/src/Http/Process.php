<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Http;

use Hiya\Base\Component;

/**
 * Process Component - Execute external processes
 * 
 * Features:
 * - Synchronous and asynchronous execution
 * - Timeout support
 * - Environment variables
 * - Working directory
 * - Input/Output handling
 * - Events (onBeforeRun, onAfterRun)
 * 
 * Example:
 * ```php
 * $process = new Process('ls -la');
 * $result = $process->timeout(30)->run();
 * 
 * if ($result->successful()) {
 *     echo $result->getOutput();
 * }
 * 
 * // Or using static factory
 * $result = Process::create('ls -la')->timeout(30)->run();
 * ```
 */
class Process extends Component
{
    /**
     * @var string Command to execute
     */
    protected $command;
    
    /**
     * @var int Timeout in seconds
     */
    protected $timeout = 60;
    
    /**
     * @var string Working directory
     */
    protected $workingDirectory;
    
    /**
     * @var array Environment variables
     */
    protected $environment = [];
    
    /**
     * @var string Input to send
     */
    protected $input;
    
    /**
     * @var resource Process handle
     */
    protected $process;
    
    /**
     * @var array Pipes
     */
    protected $pipes = [];
    
    /**
     * @var bool Is running
     */
    protected $isRunning = false;
    
    /**
     * @var float Start time
     */
    protected $startTime;
    
    /**
     * Constructor
     */
    public function __construct($command)
    {
        $this->command = $command;
    }
    
    /**
     * Set timeout
     */
    public function timeout($seconds)
    {
        $this->timeout = (int) $seconds;
        return $this;
    }
    
    /**
     * Set working directory
     */
    public function path($directory)
    {
        $this->workingDirectory = $directory;
        return $this;
    }
    
    /**
     * Set environment variables
     */
    public function env(array $environment)
    {
        $this->environment = array_merge($this->environment, $environment);
        return $this;
    }
    
    /**
     * Set input
     */
    public function input($input)
    {
        $this->input = $input;
        return $this;
    }
    
    /**
     * Run process synchronously
     */
    public function run()
    {
        $this->startTime = microtime(true);
        
        // Before run event
        $this->onBeforeRun(new \CEvent($this, [
            'command' => $this->command,
            'environment' => $this->environment,
        ]));
        
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];
        
        $this->process = proc_open(
            $this->command,
            $descriptors,
            $this->pipes,
            $this->workingDirectory,
            $this->environment
        );
        
        if (!is_resource($this->process)) {
            throw new \RuntimeException("Failed to start process: {$this->command}");
        }
        
        $this->isRunning = true;
        
        // Write input
        if ($this->input !== null) {
            fwrite($this->pipes[0], $this->input);
        }
        fclose($this->pipes[0]);
        
        // Set non-blocking
        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);
        
        $output = '';
        $errorOutput = '';
        
        while ($this->isRunning) {
            $status = proc_get_status($this->process);
            $this->isRunning = $status['running'];
            
            // Read stdout
            $stdout = fread($this->pipes[1], 8192);
            if ($stdout !== false && $stdout !== '') {
                $output .= $stdout;
            }
            
            // Read stderr
            $stderr = fread($this->pipes[2], 8192);
            if ($stderr !== false && $stderr !== '') {
                $errorOutput .= $stderr;
            }
            
            // Check timeout
            if (microtime(true) - $this->startTime > $this->timeout) {
                $this->kill();
                throw new \RuntimeException("Process timed out after {$this->timeout} seconds");
            }
            
            usleep(10000); // 10ms
        }
        
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
        
        $exitCode = proc_close($this->process);
        $executionTime = microtime(true) - $this->startTime;
        
        $result = new ProcessResult($output, $errorOutput, $exitCode, $executionTime);
        
        // After run event
        $this->onAfterRun(new \CEvent($this, [
            'command' => $this->command,
            'result' => $result,
            'execution_time' => $executionTime,
        ]));
        
        return $result;
    }
    
    /**
     * Run process asynchronously
     */
    public function runAsync()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B {$this->command}", 'r'));
        } else {
            exec("{$this->command} > /dev/null 2>&1 &");
        }
        return true;
    }
    
    /**
     * Kill running process
     */
    public function kill()
    {
        if ($this->isRunning && is_resource($this->process)) {
            proc_terminate($this->process, 9);
            $this->isRunning = false;
        }
        return $this;
    }
    
    /**
     * Get start time
     */
    public function getStartTime()
    {
        return $this->startTime;
    }
    
    /**
     * Check if running
     */
    public function isRunning()
    {
        return $this->isRunning;
    }
    
    /**
     * Static factory - Create new process instance
     */
    public static function create($command)
    {
        return new self($command);
    }
    
    // ============ EVENTS ============
    
    public function onBeforeRun($event)
    {
        $this->raiseEvent('onBeforeRun', $event);
    }
    
    public function onAfterRun($event)
    {
        $this->raiseEvent('onAfterRun', $event);
    }
}