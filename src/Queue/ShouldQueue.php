<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Queue\ShouldQueue
 * @since 1.0
 */

namespace Hiya\Queue;

/**
 * ShouldQueue Interface - Marker for jobs that should be queued
 */

interface ShouldQueue
{
    /**
     * Handle the job
     * 
     * @param array $data Job data
     */
    public function handle($data);
}