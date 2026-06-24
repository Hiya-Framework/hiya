<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Http;

/**
 * Process Result
 */
class ProcessResult
{
    /**
     * @var string Output
     */
    protected $output;
    
    /**
     * @var string Error output
     */
    protected $errorOutput;
    
    /**
     * @var int Exit code
     */
    protected $exitCode;
    
    /**
     * @var float Execution time
     */
    protected $executionTime;
    
    /**
     * Constructor
     */
    public function __construct($output, $errorOutput, $exitCode, $executionTime)
    {
        $this->output = $output;
        $this->errorOutput = $errorOutput;
        $this->exitCode = $exitCode;
        $this->executionTime = $executionTime;
    }
    
    /**
     * Check if successful
     */
    public function successful()
    {
        return $this->exitCode === 0;
    }
    
    /**
     * Check if failed
     */
    public function failed()
    {
        return $this->exitCode !== 0;
    }
    
    /**
     * Get output
     */
    public function getOutput()
    {
        return $this->output;
    }
    
    /**
     * Get error output
     */
    public function getErrorOutput()
    {
        return $this->errorOutput;
    }
    
    /**
     * Get exit code
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }
    
    /**
     * Get execution time
     */
    public function getExecutionTime()
    {
        return $this->executionTime;
    }
    
    /**
     * Get JSON decoded output
     */
    public function json()
    {
        return json_decode($this->output, true);
    }
}