<?php
/**
 * Progress Command - Demo berbagai jenis progress bar
 * 
 * @package Hiya\Console\Commands
 * @author Hiya Framework
 */

use Hiya\Console\Command;
use Hiya\Console\Style;

class ProgressCommand extends Command
{
    protected $name = 'progress';
    protected $description = 'Progress bar demo with various styles';
    protected $icon = '[P]';
    protected $group = 'progress';
    protected $aliases = ['pb', 'progress-bar'];
    
    public function handle()
    {
        // Header dengan Style
        echo Style::banner('Hiya Progress Bar Demo', '1.0', [
            'width' => 55,
            'border_char' => '─',
            'color' => 'YELLOW'
        ]);
        
        // Menu dengan Style
        $this->showMenu();
        
        while (true) {
            $choice = $this->ask(Style::color("\n  Select option", 'GREEN'), '1');
            
            switch ($choice) {
                case '1':
                    $this->basicProgress();
                    break;
                case '2':
                    $this->multiStepProgress();
                    break;
                case '3':
                    $this->nestedProgress();
                    break;
                case '4':
                    $this->spinnerProgress();
                    break;
                case '5':
                    $this->customColorProgress();
                    break;
                case '0':
                case 'q':
                case 'exit':
                    echo Style::success("\n  Thank you for using Hiya Console!\n");
                    return 0;
                default:
                    echo Style::error("  Invalid choice! Please select 0-5");
                    break;
            }
            
            $this->ask(Style::color("\n  Press Enter to continue...", 'GRAY'));
            $this->showMenu();
        }
    }
    
    protected function showMenu()
    {
        echo "\n";
        echo Style::color("  " . str_repeat("═", 50), 'CYAN') . "\n";
        echo Style::color("  " . str_pad("Available Demos", 50, " ", STR_PAD_BOTH), 'YELLOW', ['bold']) . "\n";
        echo Style::color("  " . str_repeat("═", 50), 'CYAN') . "\n";
        
        $options = [
            '1' => 'Basic Progress Bar',
            '2' => 'Multi-Step Progress',
            '3' => 'Nested Progress Bars',
            '4' => 'Spinner (Infinite)',
            '5' => 'Custom Colored Progress',
            '0' => 'Exit'
        ];
        
        foreach ($options as $key => $desc) {
            $color = ($key == '0') ? 'RED' : 'CYAN';
            echo "  " . Style::color($key . '.', $color, ['bold']) . " " . $desc . "\n";
        }
        echo "\n";
    }
    
    protected function basicProgress()
    {
        $steps = (int)$this->ask(Style::color("  Number of steps", 'YELLOW'), '50');
        $delay = (int)$this->ask(Style::color("  Delay (ms)", 'YELLOW'), '50');
        
        echo "\n" . Style::info("Basic Progress Bar") . "\n";
        echo Style::color("  " . str_repeat("─", 50), 'GRAY') . "\n";
        
        $bar = $this->progressBar($steps);
        $bar->setFormat('  <fg=cyan>%current%/%max%</> <fg=green>[%bar%]</> <fg=yellow>%percent%%</> <fg=gray>%elapsed%</>');
        $bar->start();
        
        for ($i = 1; $i <= $steps; $i++) {
            usleep($delay * 1000);
            $bar->advance();
        }
        
        $bar->finish();
        echo Style::success("\n  Progress completed!") . "\n";
    }
    
    protected function multiStepProgress()
    {
        $stages = [
            Style::icon('settings') . ' Initializing' => 10,
            Style::icon('package') . ' Processing Data' => 30,
            Style::icon('check') . ' Validating' => 15,
            Style::icon('download') . ' Saving' => 8,
            Style::icon('time') . ' Cleaning Up' => 7,
        ];
        
        $total = array_sum($stages);
        $current = 0;
        
        echo "\n" . Style::info("Multi-Step Progress") . "\n";
        echo Style::color("  " . str_repeat("─", 50), 'GRAY') . "\n";
        
        $bar = $this->progressBar($total);
        $bar->setFormat('  <fg=cyan>%current%/%max%</> <fg=green>[%bar%]</> <fg=yellow>%percent%%</> <fg=gray>%message%</>');
        $bar->start();
        
        foreach ($stages as $stage => $count) {
            for ($i = 1; $i <= $count; $i++) {
                usleep(50000);
                $current++;
                $bar->setMessage("{$stage} ({$i}/{$count})");
                $bar->setProgress($current);
            }
        }
        
        $bar->finish();
        echo Style::success("\n  Multi-step completed!") . "\n";
    }
    
    protected function nestedProgress()
    {
        echo "\n" . Style::info("Nested Progress Bars") . "\n";
        echo Style::color("  " . str_repeat("─", 50), 'GRAY') . "\n";
        
        $outerSteps = 5;
        $innerSteps = 20;
        
        $folderIcon = Style::icon('folder');
        $subfolderIcon = Style::icon('folder');
        $fileIcon = Style::icon('file');
        
        $outerBar = $this->progressBar($outerSteps);
        $outerBar->setFormat("  {$folderIcon} Outer: <fg=cyan>%current%/%max%</> <fg=green>[%bar%]</> <fg=yellow>%percent%%</>");
        $outerBar->start();
        
        for ($i = 1; $i <= $outerSteps; $i++) {
            echo "\n    {$subfolderIcon} Processing batch {$i}\n";
            
            $innerBar = $this->progressBar($innerSteps);
            $innerBar->setFormat("      {$fileIcon} Inner: <fg=cyan>%current%/%max%</> <fg=green>[%bar%]</> <fg=yellow>%percent%%</>");
            $innerBar->start();
            
            for ($j = 1; $j <= $innerSteps; $j++) {
                usleep(20000);
                $innerBar->advance();
            }
            
            $innerBar->finish();
            $outerBar->advance();
        }
        
        $outerBar->finish();
        echo Style::success("\n  Nested progress completed!") . "\n";
    }
    
    protected function spinnerProgress()
    {
        $duration = (int)$this->ask(Style::color("  Duration (seconds)", 'YELLOW'), '5');
        
        echo "\n" . Style::info("Spinner Progress") . "\n";
        echo Style::color("  " . str_repeat("─", 50), 'GRAY') . "\n";
        echo Style::warning("  Press Ctrl+C to stop early\n");
        
        // Use different spinners based on emoji support
        $spinners = Style::supportsEmoji() 
            ? ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏']
            : ['-', '\\', '|', '/', '-', '\\', '|', '/'];
        
        $tasks = ['Loading', 'Processing', 'Validating', 'Saving', 'Cleaning'];
        $i = 0;
        $taskIndex = 0;
        $start = time();
        
        while (time() - $start < $duration) {
            $elapsed = time() - $start;
            $spinner = $spinners[$i % count($spinners)];
            
            if ($elapsed % 2 == 0 && $elapsed > 0) {
                $taskIndex = ($taskIndex + 1) % count($tasks);
            }
            $task = $tasks[$taskIndex];
            
            echo "\r  {$spinner} " . Style::color("{$task}...", 'CYAN') . " " . Style::color("{$elapsed}s elapsed", 'GRAY');
            usleep(50000);
            $i++;
        }
        
        echo "\n";
        echo Style::success("  Spinner completed!") . "\n";
    }
    
    protected function customColorProgress()
    {
        $steps = (int)$this->ask(Style::color("  Number of steps", 'YELLOW'), '100');
        
        echo "\n" . Style::info("Custom Colored Progress") . "\n";
        echo Style::color("  " . str_repeat("─", 50), 'GRAY') . "\n";
        
        $colors = ['RED', 'YELLOW', 'GREEN', 'CYAN', 'MAGENTA'];
        $colorIndex = 0;
        
        $bar = $this->progressBar($steps);
        $bar->setFormat('  <fg=%color%>%current%/%max%</> <fg=green>[%bar%]</> <fg=yellow>%percent%%</> <fg=gray>%elapsed%</>');
        $bar->start();
        
        for ($i = 1; $i <= $steps; $i++) {
            usleep(30000);
            
            $percent = round($i / $steps * 100);
            if ($percent % 20 == 0 && $percent > 0) {
                $colorIndex++;
            }
            $color = strtolower($colors[$colorIndex % count($colors)]);
            
            $bar->setFormat(str_replace('%color%', $color, '  <fg=%color%>%current%/%max%</> <fg=green>[%bar%]</> <fg=yellow>%percent%%</> <fg=gray>%elapsed%</>'));
            $bar->advance();
        }
        
        $bar->finish();
        echo Style::success("\n  Custom color progress completed!") . "\n";
    }
}