<?php
// protected/jobs/TestJob.php

class TestJob
{
    public function handle($data)
    {
        $message = isset($data['message']) ? $data['message'] : 'No message';
        echo "  Processing: {$message}\n";
        sleep(1);
        echo "  Job completed!\n";
        return true;
    }
}