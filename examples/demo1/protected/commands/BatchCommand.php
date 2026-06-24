<?php
/**
 * Batch Command - Batch processing with progress bar
 * 
 * Process items in batch with progress bar, failure simulation, and statistics.
 * Useful for testing batch processing, queue workers, and performance monitoring.
 * 
 * @example
 * // Process 100 items with 50ms delay and 5% failure rate
 * php hiya batch --items=100 --delay=50 --fail-rate=5
 * 
 * // Quick run with short options
 * php hiya bp -i 50 -d 20 -f 10
 */

use Hiya\Console\Command;

class BatchCommand extends Command
{
    protected $name = 'batch';
    protected $description = 'Batch processing with progress bar and statistics';
    protected $icon = '[B]';
    protected $group = 'batch';
    protected $aliases = ['bp'];
    
    protected $options = [
        ['name' => 'items', 'short' => 'i', 'description' => 'Number of items to process', 'default' => 100],
        ['name' => 'delay', 'short' => 'd', 'description' => 'Delay between items (ms)', 'default' => 20],
        ['name' => 'fail-rate', 'short' => 'f', 'description' => 'Failure rate percentage', 'default' => 5],
    ];
    
    public function handle()
    {
        // Get options with proper defaults
        $totalItems = (int)$this->getOptionValue('items', 100);
        $delay = (int)$this->getOptionValue('delay', 20);
        $failRate = (int)$this->getOptionValue('fail-rate', 5);
        
        // Validate
        if ($totalItems <= 0) {
            $totalItems = 100;
        }
        
        $this->showHeader($totalItems, $failRate, $delay);
        
        $successCount = 0;
        $failCount = 0;
        $failedItems = [];
        $startTime = microtime(true);
        
        // Progress bar
        $bar = $this->progressBar($totalItems);
        $bar->setFormat('  <fg=cyan>%current%/%max%</> <fg=green>[%bar%]</> <fg=yellow>%percent%%</> <fg=gray>%elapsed%</>');
        $bar->start();
        
        for ($i = 1; $i <= $totalItems; $i++) {
            usleep($delay * 1000);
            
            $isFailed = (rand(1, 100) <= $failRate);
            
            if ($isFailed) {
                $failCount++;
                $failedItems[] = $i;
            } else {
                $successCount++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $executionTime = round(microtime(true) - $startTime, 2);
        
        $this->showSummary($totalItems, $successCount, $failCount, $failedItems, $executionTime);
        
        return $failCount > 0 ? 1 : 0;
    }
    
    protected function getOptionValue($name, $default = null)
    {
        global $argv;
        
        // Check for --name=value
        foreach ($argv as $arg) {
            if (strpos($arg, "--{$name}=") === 0) {
                return substr($arg, strlen("--{$name}="));
            }
            if (strpos($arg, "-{$name}=") === 0) {
                return substr($arg, strlen("-{$name}="));
            }
        }
        
        // Check for next value after --name
        for ($i = 0; $i < count($argv); $i++) {
            if ($argv[$i] === "--{$name}" && isset($argv[$i + 1]) && $argv[$i + 1][0] !== '-') {
                return $argv[$i + 1];
            }
            if ($argv[$i] === "-{$name}" && isset($argv[$i + 1]) && $argv[$i + 1][0] !== '-') {
                return $argv[$i + 1];
            }
        }
        
        return $default;
    }
    
    protected function showHeader($totalItems, $failRate, $delay)
    {
        // Header box
        $this->line("\n  <fg=cyan;options=bold>╔══════════════════════════════════════════════════╗</>");
        $this->line("  <fg=cyan;options=bold>║</>      <fg=white>Batch Processing System</>                  <fg=cyan;options=bold>║</>");
        $this->line("  <fg=cyan;options=bold>╠══════════════════════════════════════════════════╣</>");
        
        // Use table for configuration
        $table = $this->table();
        $table->addField('CONFIGURATION', 'label', false, 'yellow');
        $table->addField('VALUE', 'value', false, 'white');
        
        $table->setData([
            ['label' => 'Total Items', 'value' => number_format($totalItems)],
            ['label' => 'Failure Rate', 'value' => $failRate . '%'],
            ['label' => 'Delay', 'value' => $delay . ' ms'],
        ]);
        
        $table->render();
        
        $this->line("  <fg=cyan;options=bold>╚══════════════════════════════════════════════════╝</>");
        $this->line("");
    }
    
    protected function showSummary($total, $success, $failed, $failedItems, $executionTime)
    {
        $successRate = $total > 0 ? round(($success / $total) * 100, 2) : 0;
        
        // Summary box
        $this->line("\n  <fg=cyan;options=bold>╔══════════════════════════════════════════════════╗</>");
        $this->line("  <fg=cyan;options=bold>║</>          <fg=white>Processing Summary</>                       <fg=cyan;options=bold>║</>");
        $this->line("  <fg=cyan;options=bold>╠══════════════════════════════════════════════════╣</>");
        
        // Use table for summary
        $table = $this->table();
        $table->addField('METRIC', 'label', false, 'yellow');
        $table->addField('RESULT', 'value', false, 'white');
        
        $data = [
            ['label' => 'Total Items', 'value' => number_format($total)],
            ['label' => '✓ Successful', 'value' => $this->colorize(number_format($success), 'green')],
            ['label' => '✗ Failed', 'value' => $this->colorize(number_format($failed), 'red')],
            ['label' => 'Success Rate', 'value' => $this->colorize($successRate . '%', 'yellow')],
            ['label' => 'Execution Time', 'value' => $this->colorize($executionTime . ' seconds', 'cyan')]
        ];
        
        $table->setData($data);
        $table->render();
        
        // Show failed items if any
        if (!empty($failedItems)) {
            $this->line("  <fg=cyan;options=bold>╠══════════════════════════════════════════════════╣</>");
            
            $failedList = implode(', ', array_slice($failedItems, 0, 10));
            if (strlen($failedList) > 36) {
                $failedList = substr($failedList, 0, 33) . '...';
            }
            
            // Use table for failed items
            $failedTable = $this->table();
            $failedTable->addField('FAILED IDs', 'ids', false, 'red');
            $failedTable->setData([['ids' => $failedList]]);
            $failedTable->render();
            
            if (count($failedItems) > 10) {
                $more = count($failedItems) - 10;
                $this->line("  <fg=cyan;options=bold>║</> <fg=red>" . str_pad("... and {$more} more", 38, " ", STR_PAD_BOTH) . "</> <fg=cyan;options=bold>║</>");
            }
        }
        
        $this->line("  <fg=cyan;options=bold>╚══════════════════════════════════════════════════╝</>");
        
        // Final message
        $this->line("");
        if ($failed > 0) {
            $this->warning("  Batch completed with {$failed} failures");
        } else {
            $this->success("  Batch completed successfully!");
        }
        $this->line("");
    }
}