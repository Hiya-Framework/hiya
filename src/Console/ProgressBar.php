<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Console\ProgressBar
 * @since 1.0
 */

namespace Hiya\Console;

class ProgressBar
{
    protected $total;
    protected $current = 0;
    protected $width = 50;
    protected $format = ' %current%/%max% %bar% %percent%% %elapsed%';
    protected $startTime;
    protected $message = '';
    protected $lastOutput = '';
    protected $refreshRate = 0.1;
    protected $lastRefresh = 0;
    
    // Cache for color codes
    protected static $colorCache = [];
    
    // Bar characters with fallback
    protected $barChars = [
        'filled' => '█',
        'empty' => '░',
        'filled_ascii' => '=',
        'empty_ascii' => '-',
    ];
    
    public function __construct($total = 0)
    {
        $this->total = $total;
        $this->startTime = microtime(true);
        $this->detectBarChars();
    }
    
    /**
     * Detect best bar characters based on terminal support
     */
    protected function detectBarChars()
    {
        $supportsUnicode = Style::supportsEmoji();
        
        if (!$supportsUnicode) {
            $this->barChars['filled'] = $this->barChars['filled_ascii'];
            $this->barChars['empty'] = $this->barChars['empty_ascii'];
        }
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }
    
    public function setWidth($width)
    {
        $this->width = max(10, min(100, $width)); // Limit width between 10-100
        return $this;
    }
    
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
    
    public function setRefreshRate($seconds)
    {
        $this->refreshRate = max(0.05, min(1, $seconds));
        return $this;
    }
    
    public function start()
    {
        $this->current = 0;
        $this->display();
    }
    
    public function advance($step = 1)
    {
        $this->current = min($this->current + $step, $this->total);
        
        // Only refresh if enough time passed (throttle output)
        $now = microtime(true);
        if (($now - $this->lastRefresh) >= $this->refreshRate || $this->current >= $this->total) {
            $this->lastRefresh = $now;
            $this->display();
        }
    }
    
    public function setProgress($current)
    {
        $this->current = min($current, $this->total);
        $this->display();
    }
    
    public function finish()
    {
        $this->current = $this->total;
        $this->display();
        echo "\n";
    }
    
    /**
     * Calculate ETA with better precision
     */
    protected function calculateETA()
    {
        if ($this->current === 0 || $this->current >= $this->total) {
            return 0;
        }
        
        $elapsed = microtime(true) - $this->startTime;
        $rate = $this->current / $elapsed;
        $remaining = ($this->total - $this->current) / $rate;
        
        return max(0, round($remaining));
    }
    
    /**
     * Format time duration
     */
    protected function formatTime($seconds)
    {
        if ($seconds < 60) {
            return round($seconds, 1) . 's';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
        } else {
            return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
        }
    }
    
    /**
     * Get memory usage
     */
    protected function getMemoryUsage()
    {
        $memory = memory_get_usage();
        if ($memory < 1024) {
            return $memory . 'B';
        } elseif ($memory < 1048576) {
            return round($memory / 1024, 1) . 'KB';
        } else {
            return round($memory / 1048576, 1) . 'MB';
        }
    }
    
    protected function display()
    {
        $percent = $this->total > 0 ? round($this->current / $this->total * 100) : 0;
        $filled = round($percent / 100 * $this->width);
        $empty = $this->width - $filled;
        
        $bar = str_repeat($this->barChars['filled'], $filled) . str_repeat($this->barChars['empty'], $empty);
        
        $elapsed = microtime(true) - $this->startTime;
        $eta = $this->calculateETA();
        
        // Prepare replacements
        $replacements = [
            '%current%' => number_format($this->current),
            '%max%' => number_format($this->total),
            '%bar%' => $bar,
            '%percent%' => $percent,
            '%elapsed%' => $this->formatTime($elapsed),
            '%estimated%' => $this->formatTime($eta),
            '%eta%' => $this->formatTime($eta),
            '%message%' => $this->message,
            '%memory%' => $this->getMemoryUsage(),
            '%rate%' => $this->current > 0 ? round($this->current / $elapsed, 1) . '/s' : '0/s',
        ];
        
        $output = str_replace(array_keys($replacements), array_values($replacements), $this->format);
        
        // Parse color tags
        $output = $this->parseColorTags($output);
        
        // Only update if output changed (reduces flickering)
        if ($output !== $this->lastOutput || $this->current >= $this->total) {
            echo "\r\033[2K" . $output;
            $this->lastOutput = $output;
        }
    }
    
    /**
     * Parse color tags with caching
     */
    protected function parseColorTags($text)
    {
        // Parse <fg=color> tags
        $text = preg_replace_callback('/<fg=([a-z]+)>/', function($matches) {
            $color = $matches[1];
            
            // Check cache
            if (!isset(self::$colorCache[$color])) {
                $colors = [
                    'red' => '31', 'green' => '32', 'yellow' => '33',
                    'blue' => '34', 'magenta' => '35', 'cyan' => '36',
                    'white' => '37', 'gray' => '90',
                ];
                self::$colorCache[$color] = "\033[" . ($colors[$color] ?? '37') . "m";
            }
            return self::$colorCache[$color];
        }, $text);
        
        // Parse <options=bold>
        $text = preg_replace('/<options=bold>/', "\033[1m", $text);
        
        // Parse <fg=color;options=bold>
        $text = preg_replace_callback('/<fg=([a-z]+);options=bold>/', function($matches) {
            $color = $matches[1];
            $cacheKey = $color . '_bold';
            
            if (!isset(self::$colorCache[$cacheKey])) {
                $colors = [
                    'red' => '31', 'green' => '32', 'yellow' => '33',
                    'blue' => '34', 'magenta' => '35', 'cyan' => '36',
                    'white' => '37', 'gray' => '90',
                ];
                self::$colorCache[$cacheKey] = "\033[1;" . ($colors[$color] ?? '37') . "m";
            }
            return self::$colorCache[$cacheKey];
        }, $text);
        
        // Parse <options=underline>
        $text = preg_replace('/<options=underline>/', "\033[4m", $text);
        
        // Parse <fg=color;options=underline>
        $text = preg_replace_callback('/<fg=([a-z]+);options=underline>/', function($matches) {
            $color = $matches[1];
            $cacheKey = $color . '_underline';
            
            if (!isset(self::$colorCache[$cacheKey])) {
                $colors = [
                    'red' => '31', 'green' => '32', 'yellow' => '33',
                    'blue' => '34', 'magenta' => '35', 'cyan' => '36',
                    'white' => '37', 'gray' => '90',
                ];
                self::$colorCache[$cacheKey] = "\033[4;" . ($colors[$color] ?? '37') . "m";
            }
            return self::$colorCache[$cacheKey];
        }, $text);
        
        // Close tag
        $text = str_replace('</>', "\033[0m", $text);
        
        return $text;
    }
    
    /**
     * Create a themed progress bar
     */
    public function setTheme($theme)
    {
        $themes = [
            'default' => ['filled' => '█', 'empty' => '░'],
            'simple' => ['filled' => '=', 'empty' => '-'],
            'arrow' => ['filled' => '>', 'empty' => ' '],
            'block' => ['filled' => '■', 'empty' => '□'],
            'dot' => ['filled' => '●', 'empty' => '○'],
        ];
        
        if (isset($themes[$theme])) {
            $this->barChars['filled'] = $themes[$theme]['filled'];
            $this->barChars['empty'] = $themes[$theme]['empty'];
        }
        
        return $this;
    }
}