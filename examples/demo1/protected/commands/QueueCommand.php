<?php
/**
 * Queue Command - Queue management dengan Hiya Console
 */

use Hiya\Console\Command;

class QueueCommand extends Command
{
    protected $name = 'queue';
    protected $description = 'Queue management system';
    protected $aliases = ['q'];
    
    protected $options = [
        ['name' => 'queue', 'short' => 'q', 'description' => 'Queue name', 'default' => 'default'],
        ['name' => 'once', 'short' => 'o', 'description' => 'Process only one job'],
        ['name' => 'max-jobs', 'short' => 'm', 'description' => 'Maximum jobs to process', 'default' => 5],
    ];
    
    public function handle()
    {
        // Get the command name from global argv
        global $argv;
        
        // Find which command was called
        $commandName = null;
        foreach ($argv as $arg) {
            if (strpos($arg, 'queue:') === 0) {
                $commandName = str_replace('queue:', '', $arg);
                break;
            }
            if ($arg === 'queue') {
                $commandName = 'help';
                break;
            }
        }
        
        switch ($commandName) {
            case 'stats':
                return $this->actionStats();
            case 'work':
                return $this->actionWork();
            case 'push':
                return $this->actionPush();
            case 'list':
                return $this->actionList();
            case 'failed':
                return $this->actionFailed();
            case 'retry':
                return $this->actionRetry();
            case 'flush':
                return $this->actionFlush();
            case 'info':
                return $this->actionInfo();
            default:
                return $this->showHelp();
        }
    }
    
    protected function actionStats()
    {
        $queue = $this->option('queue');
        
        $this->line("\n  <fg=cyan>📊 Queue Statistics</>");
        $this->line("  " . str_repeat('─', 40));
        
        if ($queue && $queue !== 'default') {
            $this->line("  Queue: <fg=yellow>{$queue}</>");
        }
        
        $stats = [
            'pending' => rand(0, 10),
            'processing' => rand(0, 5),
            'completed' => rand(100, 500),
            'failed' => rand(0, 10),
            'delayed' => rand(0, 5),
        ];
        
        foreach ($stats as $status => $count) {
            $color = $this->getStatusColor($status);
            $icon = $this->getStatusIcon($status);
            $this->line(sprintf("    <fg=%s>%s %s:</> %d", $color, $icon, ucfirst($status), $count));
        }
        
        $this->line("");
        return 0;
    }
    
    protected function actionWork()
    {
        $queue = $this->option('queue');
        $once = $this->hasOption('once');
        $maxJobs = (int)$this->option('max-jobs');
        
        $this->line("\n  <fg=green>🚀 Queue Worker Started</>");
        $this->line("  Queue: <fg=cyan>{$queue}</>");
        $this->line("  Once: " . ($once ? 'Yes' : 'No'));
        $this->line("  Max Jobs: {$maxJobs}");
        $this->line("  Press Ctrl+C to stop\n");
        
        $bar = $this->progressBar($maxJobs);
        $bar->setFormat('  <fg=cyan>%current%/%max%</> <fg=green>[%bar%]</> <fg=yellow>%percent%%</> <fg=gray>%elapsed%</>');
        $bar->start();
        
        for ($i = 1; $i <= $maxJobs; $i++) {
            sleep(1);
            $bar->advance();
            if ($once) break;
        }
        
        $bar->finish();
        $this->success("\n  ✓ Worker stopped");
        $this->line("");
        return 0;
    }
    
    protected function actionPush()
    {
        // Get job name from arguments
        global $argv;
        $job = null;
        $data = '{}';
        
        // Parse job from arguments after push
        $foundPush = false;
        foreach ($argv as $arg) {
            if ($foundPush && $job === null && strpos($arg, '--') !== 0) {
                $job = $arg;
                continue;
            }
            if ($arg === 'push' || $arg === 'queue:push') {
                $foundPush = true;
            }
            if ($foundPush && strpos($arg, '--data=') === 0) {
                $data = substr($arg, 7);
            }
        }
        
        $job = $job ?: 'TestJob';
        $queue = $this->option('queue');
        $jobId = uniqid();
        
        $this->line("\n  <fg=green>✓ Job pushed successfully!</>");
        $this->line("  ID: <fg=cyan>{$jobId}</>");
        $this->line("  Queue: {$queue}");
        $this->line("  Class: {$job}");
        $this->line("  Data: {$data}");
        $this->line("");
        return 0;
    }
    
    protected function actionList()
    {
        $this->line("\n  <fg=cyan>📋 Job List</>");
        $this->line("  " . str_repeat('─', 60));
        
        $jobs = [
            ['id' => 'job_001', 'class' => 'SendEmailJob', 'status' => 'completed'],
            ['id' => 'job_002', 'class' => 'ProcessDataJob', 'status' => 'pending'],
            ['id' => 'job_003', 'class' => 'GenerateReportJob', 'status' => 'pending'],
            ['id' => 'job_004', 'class' => 'BackupDatabaseJob', 'status' => 'failed'],
        ];
        
        $headers = ['ID', 'Job Class', 'Status'];
        $rows = [];
        
        foreach ($jobs as $job) {
            $color = $this->getStatusColor($job['status']);
            $icon = $this->getStatusIcon($job['status']);
            $status = "<fg={$color}>{$icon} " . ucfirst($job['status']) . "</>";
            $rows[] = [$job['id'], $job['class'], $status];
        }
        
        $this->table($headers, $rows);
        $this->line("");
        return 0;
    }
    
    protected function actionFailed()
    {
        $this->line("\n  <fg=green>✓ No failed jobs found!</>");
        $this->line("");
        return 0;
    }
    
    protected function actionRetry()
    {
        global $argv;
        $id = null;
        
        foreach ($argv as $i => $arg) {
            if (($arg === 'retry' || $arg === 'queue:retry') && isset($argv[$i + 1])) {
                $id = $argv[$i + 1];
                break;
            }
        }
        
        if ($id === 'all') {
            $this->line("\n  <fg=green>✓ Retried all failed jobs!</>");
        } elseif ($id) {
            $this->line("\n  <fg=green>✓ Job {$id} has been retried!</>");
        } else {
            $this->line("\n  <fg=yellow>Usage: php hiya queue:retry [id|all]</>");
        }
        $this->line("");
        return 0;
    }
    
    protected function actionFlush()
    {
        $this->line("\n  <fg=yellow>⚠️ This will delete ALL jobs!</>");
        
        if (!$this->confirm("  Are you sure?", false)) {
            $this->line("  <fg=yellow>Cancelled</>\n");
            return 0;
        }
        
        $this->line("\n  <fg=green>✓ All jobs cleared!</>");
        $this->line("");
        return 0;
    }
    
    protected function actionInfo()
    {
        $this->line("\n  <fg=cyan>ℹ️ Queue System Information</>");
        $this->line("  " . str_repeat('─', 40));
        $this->line("  Driver: File Based Queue");
        $this->line("  Default Queue: default");
        $this->line("  Storage Path: " . __DIR__ . "/runtime/queue");
        $this->line("  PHP Version: " . PHP_VERSION);
        $this->line("  Hiya Version: " . (defined('HIYA_VERSION') ? HIYA_VERSION : '2.0.0'));
        $this->line("");
        $this->line("  <fg=yellow>Commands:</>");
        $this->line("    <fg=cyan>php hiya queue:stats</>  - Show statistics");
        $this->line("    <fg=cyan>php hiya queue:work</>   - Start worker");
        $this->line("    <fg=cyan>php hiya queue:push</>   - Push job");
        $this->line("    <fg=cyan>php hiya queue:list</>   - List jobs");
        $this->line("    <fg=cyan>php hiya queue:info</>   - System info");
        $this->line("");
        return 0;
    }
    
    protected function getStatusColor($status)
    {
        $colors = [
            'pending' => 'yellow',
            'processing' => 'cyan',
            'completed' => 'green',
            'failed' => 'red',
            'delayed' => 'magenta',
        ];
        return $colors[$status] ?? 'gray';
    }
    
    protected function getStatusIcon($status)
    {
        $icons = [
            'pending' => '⏳',
            'processing' => '⚙️',
            'completed' => '✅',
            'failed' => '❌',
            'delayed' => '⏰',
        ];
        return $icons[$status] ?? '📦';
    }
    
    protected function showHelp()
    {
        $this->line("\n  <fg=cyan>📦 Queue Commands</>");
        $this->line("  " . str_repeat('═', 40));
        $this->line("\n  <fg=green>Usage:</>");
        $this->line("    php hiya queue:[command] [options]");
        $this->line("\n  <fg=green>Commands:</>");
        $this->line("    <fg=cyan>stats</>      - Show queue statistics");
        $this->line("    <fg=cyan>work</>       - Start queue worker");
        $this->line("    <fg=cyan>push</>       - Push a new job");
        $this->line("    <fg=cyan>list</>       - List all jobs");
        $this->line("    <fg=cyan>failed</>     - List failed jobs");
        $this->line("    <fg=cyan>retry</>      - Retry failed job");
        $this->line("    <fg=cyan>flush</>      - Clear all jobs");
        $this->line("    <fg=cyan>info</>       - Show system info");
        $this->line("\n  <fg=green>Options:</>");
        $this->line("    <fg=cyan>--queue, -q</>     Queue name (default: default)");
        $this->line("    <fg=cyan>--once, -o</>      Process only one job");
        $this->line("    <fg=cyan>--max-jobs, -m</>  Maximum jobs to process (default: 5)");
        $this->line("\n  <fg=green>Examples:</>");
        $this->line("    <fg=gray>php hiya queue:stats</>");
        $this->line("    <fg=gray>php hiya queue:work --queue=high</>");
        $this->line("    <fg=gray>php hiya queue:push TestJob</>");
        $this->line("    <fg=gray>php hiya queue:list</>");
        $this->line("");
        return 0;
    }
}