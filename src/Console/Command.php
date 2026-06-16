<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Console\Command
 * @since 1.0
 */

namespace Hiya\Console;

use CConsoleCommand;

abstract class Command extends CConsoleCommand
{
    protected $name;
    protected $description = '';
    protected $aliases = [];
    protected $arguments = [];
    protected $options = [];
    protected $icon = '🔹';
    protected $group = 'general';
    protected $verbosity = 0;
    
    protected $colors = [
        'black' => '0;30', 'dark_gray' => '1;30',
        'red' => '0;31', 'light_red' => '1;31',
        'green' => '0;32', 'light_green' => '1;32',
        'brown' => '0;33', 'yellow' => '1;33',
        'blue' => '0;34', 'light_blue' => '1;34',
        'purple' => '0;35', 'light_purple' => '1;35',
        'cyan' => '0;36', 'light_cyan' => '1;36',
        'light_gray' => '0;37', 'white' => '1;37',
    ];
    
    protected $styles = [
        'bold' => '1', 'dim' => '2', 'italic' => '3',
        'underline' => '4', 'blink' => '5', 'reverse' => '7', 'hidden' => '8',
    ];
    
    // Icons with ASCII fallback
    protected static $useEmoji = null;
    
    /**
     * Constructor
     */
    public function __construct($name, $runner)
    {
        parent::__construct($name, $runner);
        $this->detectEmojiSupport();
    }
    
    /**
     * Detect if terminal supports emoji
     */
    protected function detectEmojiSupport()
    {
        if (self::$useEmoji === null) {
            if (DIRECTORY_SEPARATOR === '\\') {
                $version = php_uname('r');
                self::$useEmoji = version_compare($version, '10.0.17134', '>=');
            } else {
                $term = getenv('TERM');
                self::$useEmoji = !($term && in_array($term, ['dumb', 'unknown']));
            }
        }
    }
    
    /**
     * Get icon (with fallback to ASCII)
     */
    protected function getIconChar($emoji, $ascii)
    {
        return self::$useEmoji ? $emoji : $ascii;
    }
    
    abstract public function handle();
    
    public function init()
    {
        parent::init();
    }
    
    public function actionIndex()
    {
        return $this->handle();
    }
    
    /**
     * Run method for Yii compatibility
     */
    public function run($args)
    {
        array_shift($args);
        return $this->handle();
    }
    
    /**
     * Get command name
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Get command description
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * Get command aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }
    
    /**
     * Get command icon
     */
    public function getIcon()
    {
        return $this->icon;
    }
    
    /**
     * Get command group
     */
    public function getGroup()
    {
        return $this->group;
    }
    
    /**
     * Get command arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }
    
    /**
     * Get command options
     */
    public function getOptions()
    {
        return $this->options;
    }
    
    /**
     * Set verbosity level
     */
    public function setVerbosity($level)
    {
        $this->verbosity = $level;
    }
    
    /**
     * Check if verbose output is enabled
     */
    protected function isVerbose()
    {
        return $this->verbosity > 0;
    }
    
    /**
     * Output verbose message
     */
    protected function verbose($message, $color = 'gray')
    {
        if ($this->isVerbose()) {
            $this->line("[VERBOSE] " . $message, $color);
        }
    }
    
    /**
     * Get argument value
     */
    protected function argument($key, $default = null)
    {
        global $argv;
        $args = array_slice($argv, 2);
        return $args[$key] ?? $default;
    }
    
    /**
     * Get option value
     */
    protected function option($name, $default = null)
    {
        global $argv;
        foreach ($argv as $i => $arg) {
            if (strpos($arg, "--{$name}=") === 0) {
                return substr($arg, strlen("--{$name}="));
            }
            if ($arg === "--{$name}") {
                if (isset($argv[$i + 1]) && $argv[$i + 1][0] !== '-') {
                    return $argv[$i + 1];
                }
                return true;
            }
            if (strpos($arg, "-{$name}=") === 0) {
                return substr($arg, strlen("-{$name}="));
            }
            if ($arg === "-{$name}") {
                if (isset($argv[$i + 1]) && $argv[$i + 1][0] !== '-') {
                    return $argv[$i + 1];
                }
                return true;
            }
        }
        return $default;
    }
    
    /**
     * Check if option exists
     */
    protected function hasOption($name)
    {
        return $this->option($name, false) !== false;
    }
    
    /**
     * Ask question
     */
    protected function ask($question, $default = null)
    {
        echo $this->colorize($question, 'green') . ($default !== null ? " [{$default}]" : '') . ': ';
        $answer = trim(fgets(STDIN));
        return $answer !== '' ? $answer : $default;
    }
    
    /**
     * Ask with validation
     */
    protected function askValid($question, $rules = [], $default = null)
    {
        while (true) {
            $answer = $this->ask($question, $default);
            $errors = $this->validate($answer, $rules);
            
            if (empty($errors)) {
                return $answer;
            }
            
            foreach ($errors as $error) {
                $this->error($error);
            }
        }
    }
    
    /**
     * Confirm action
     */
    public function confirm($question, $default = false)
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        echo $this->colorize($question, 'yellow') . " ({$defaultText}): ";
        $answer = strtolower(trim(fgets(STDIN)));
        
        if ($answer === '') {
            return $default;
        }
        
        return in_array($answer, ['y', 'yes', 'true', '1']);
    }
    
    /**
     * Ask for secret input
     */
    protected function secret($question)
    {
        echo $this->colorize($question, 'green') . ': ';
        
        if (PHP_OS_FAMILY === 'Windows') {
            $answer = trim(fgets(STDIN));
        } else {
            system('stty -echo');
            $answer = trim(fgets(STDIN));
            system('stty echo');
            echo "\n";
        }
        
        return $answer;
    }
    
    /**
     * Choose from options
     */
    protected function choice($question, $options, $default = null)
    {
        $this->line($question, 'cyan');
        foreach ($options as $i => $option) {
            $this->line("  [{$i}] {$option}", 'gray');
        }
        
        $answer = $this->ask("Select an option", $default);
        return $options[$answer] ?? $answer;
    }
    
    /**
     * Choose with validation
     */
    protected function choiceValid($question, $options, $default = null)
    {
        $this->line($question, 'cyan');
        foreach ($options as $i => $option) {
            $this->line("  [{$i}] {$option}", 'gray');
        }
        
        $rules = ['in' => [array_keys($options)]];
        $answer = $this->askValid("Select an option", $rules, $default);
        return $options[$answer];
    }
    
    /**
     * Parse color tags in text
     */
    protected function parseColorTags($text)
    {
        $text = preg_replace_callback('/<fg=([a-z]+)>/', function($matches) {
            $colors = [
                'red' => '31', 'green' => '32', 'yellow' => '33',
                'blue' => '34', 'magenta' => '35', 'cyan' => '36',
                'white' => '37', 'gray' => '90',
            ];
            return "\033[" . ($colors[$matches[1]] ?? '37') . "m";
        }, $text);
        
        $text = preg_replace('/<options=bold>/', "\033[1m", $text);
        
        $text = preg_replace_callback('/<fg=([a-z]+);options=bold>/', function($matches) {
            $colors = [
                'red' => '31', 'green' => '32', 'yellow' => '33',
                'blue' => '34', 'magenta' => '35', 'cyan' => '36',
                'white' => '37', 'gray' => '90',
            ];
            return "\033[1;" . ($colors[$matches[1]] ?? '37') . "m";
        }, $text);
        
        $text = preg_replace_callback('/<fg=([a-z]+);options=underline>/', function($matches) {
            $colors = [
                'red' => '31', 'green' => '32', 'yellow' => '33',
                'blue' => '34', 'magenta' => '35', 'cyan' => '36',
                'white' => '37', 'gray' => '90',
            ];
            return "\033[4;" . ($colors[$matches[1]] ?? '37') . "m";
        }, $text);
        
        $text = preg_replace('/<options=underline>/', "\033[4m", $text);
        $text = str_replace('</>', "\033[0m", $text);
        
        return $text;
    }
    
    /**
     * Output line
     */
    protected function line($message, $color = null)
    {
        $message = $this->parseColorTags($message);
        
        if ($color && !preg_match('/\033\[/', $message)) {
            $message = $this->colorize($message, $color);
        }
        
        echo $message . "\n";
    }
    
    /**
     * Output info message
     */
    protected function info($message)
    {
        $this->line("  " . $this->getIconChar('✓', '[✓]') . " " . $message, 'green');
    }
    
    /**
     * Output success message
     */
    protected function success($message)
    {
        $this->line("  " . $this->getIconChar('✓', '[✓]') . " " . $message, 'green');
    }
    
    /**
     * Output error message
     */
    protected function error($message)
    {
        $this->line("  " . $this->getIconChar('✗', '[✗]') . " " . $message, 'red');
    }
    
    /**
     * Output warning message
     */
    protected function warning($message)
    {
        $this->line("  " . $this->getIconChar('⚠', '[!]') . " " . $message, 'yellow');
    }
    
    /**
     * Output comment/muted message
     */
    protected function comment($message)
    {
        $this->line("  // " . $message, 'gray');
    }
    
    /**
     * Create progress bar
     */
    protected function progressBar($total = 0)
    {
        return new ProgressBar($total);
    }
    
    /**
     * Create a new table instance
     */
    protected function table($headers = null, $rows = null, $itemName = 'Row')
    {
        $table = new Table($itemName);
        
        if ($headers) {
            $table->setHeaders($headers);
        }
        
        if ($rows) {
            $table->setRows($rows);
        }
        
        return $table;
    }
    
    /**
     * Quick render table
     */
    protected function renderTable($headers, $rows, $itemName = 'Row')
    {
        $table = $this->table($headers, $rows, $itemName);
        $table->render();
    }
    
    /**
     * Create advanced table with field configuration
     */
    protected function advancedTable($fields = [], $data = [])
    {
        $table = new Table();
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $key = $field['key'] ?? $field['name'];
            $manipulator = $field['manipulator'] ?? null;
            $color = $field['color'] ?? 'white';
            $table->addField($name, $key, $manipulator, $color);
        }
        
        if (!empty($data)) {
            $table->setData($data);
        }
        
        return $table;
    }
    
    /**
     * Render key-value table
     */
    protected function keyValueTable($data, $title = null)
    {
        if ($title) {
            $this->line("\n  <fg=cyan;options=bold>" . $title . "</>");
            $this->line("  <fg=gray>" . str_repeat('─', strlen($title) + 2) . "</>");
        }
        
        $maxKeyLength = max(array_map('strlen', array_keys($data)));
        
        foreach ($data as $key => $value) {
            $paddedKey = str_pad($key, $maxKeyLength);
            $this->line("  <fg=yellow>{$paddedKey}:</> <fg=white>{$value}</>");
        }
        
        if ($title) {
            $this->line("");
        }
    }
    
    /**
     * Render a bordered box
     */
    protected function box($content, $title = null, $borderColor = 'cyan')
    {
        $lines = is_array($content) ? $content : explode("\n", $content);
        $maxLength = max(array_map('strlen', $lines));
        $maxLength = max($maxLength, 40);
        
        // Top border with title
        if ($title) {
            $titleText = " {$title} ";
            $titleLength = strlen($titleText);
            $dashCount = ($maxLength - $titleLength) / 2;
            $topBorder = str_repeat('─', floor($dashCount)) . $titleText . str_repeat('─', ceil($dashCount));
        } else {
            $topBorder = str_repeat('─', $maxLength + 2);
        }
        
        $this->line("  <fg={$borderColor};options=bold>┌{$topBorder}┐</>");
        
        // Content
        foreach ($lines as $line) {
            $padding = $maxLength - strlen($line);
            $this->line("  <fg={$borderColor};options=bold>│</> <fg=white>{$line}</>" . str_repeat(' ', $padding) . " <fg={$borderColor};options=bold>│</>");
        }
        
        // Bottom border
        $bottomBorder = str_repeat('─', $maxLength + 2);
        $this->line("  <fg={$borderColor};options=bold>└{$bottomBorder}┘</>");
    }
    
    /**
     * Create a divider line
     */
    protected function divider($char = '─', $length = 60, $color = 'gray')
    {
        $this->line("  <fg={$color}>" . str_repeat($char, $length) . "</>");
    }
    
    /**
     * Create a bullet list
     */
    protected function listing($items, $bullet = '•', $color = 'white')
    {
        foreach ($items as $item) {
            $this->line("  <fg={$color}> {$bullet} {$item}</>");
        }
    }
    
    /**
     * Create a numbered list
     */
    protected function numberedList($items, $color = 'white')
    {
        foreach ($items as $index => $item) {
            $number = $index + 1;
            $this->line("  <fg=cyan>{$number}.</> <fg={$color}>{$item}</>");
        }
    }
    
    /**
     * Create a status badge
     */
    protected function statusBadge($label, $status)
    {
        $statuses = [
            'success' => ['green', $this->getIconChar('✓', '[✓]')],
            'error' => ['red', $this->getIconChar('✗', '[✗]')],
            'warning' => ['yellow', $this->getIconChar('⚠', '[!]')],
            'info' => ['blue', $this->getIconChar('ℹ', '[i]')],
            'pending' => ['yellow', $this->getIconChar('○', '[ ]')],
            'running' => ['cyan', $this->getIconChar('▶', '[>]')],
            'stopped' => ['gray', $this->getIconChar('■', '[#]')],
        ];
        
        $color = $statuses[$status][0] ?? 'gray';
        $icon = $statuses[$status][1] ?? '●';
        
        return $this->colorize($icon, $color) . " " . $this->colorize($label, $color);
    }
    
    /**
     * Show progress percentage
     */
    protected function showProgress($current, $total, $label = 'Progress')
    {
        $percent = $total > 0 ? round($current / $total * 100) : 0;
        $width = 30;
        $filled = round($width * $current / $total);
        $bar = str_repeat('█', $filled) . str_repeat('░', $width - $filled);
        
        $this->line(sprintf(
            "  <fg=yellow>%-10s</> <fg=cyan>[%s]</> <fg=white>%3d%%</> <fg=gray>(%d/%d)</>",
            $label . ':',
            $bar,
            $percent,
            $current,
            $total
        ));
    }
    
    /**
     * New line
     */
    protected function newLine($count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            echo "\n";
        }
    }
    
    /**
     * Colorize text
     */
    protected function colorize($text, $color)
    {
        $code = $this->colors[$color] ?? '1;37';
        return "\033[{$code}m{$text}\033[0m";
    }
    
    /**
     * Show command help
     */
    protected function showHelp()
    {
        $this->line("\n  <fg=cyan;options=bold>" . $this->name . "</>");
        $this->line("  <fg=gray>" . $this->description . "</>\n");
        $this->line("  <fg=green>Usage:</>");
        $this->line("    php Hiya " . $this->name . " [arguments] [options]\n");
        
        if (!empty($this->arguments)) {
            $this->line("  <fg=green>Arguments:</>");
            foreach ($this->arguments as $arg) {
                $required = $arg['required'] ? '<fg=red>required</>' : '<fg=yellow>optional</>';
                $this->line(sprintf("    <fg=cyan>%-15s</> <fg=gray>%s</> %s", 
                    $arg['name'], $arg['description'], $required));
            }
            $this->line("");
        }
        
        if (!empty($this->options)) {
            $this->line("  <fg=green>Options:</>");
            foreach ($this->options as $opt) {
                $short = isset($opt['short']) ? "-{$opt['short']}, " : '    ';
                $this->line(sprintf("    %s<fg=cyan>--%-12s</> <fg=gray>%s</>", 
                    $short, $opt['name'], $opt['description']));
            }
            $this->line("");
        }
        
        if (!empty($this->aliases)) {
            $this->line("  <fg=green>Aliases:</>");
            $this->line("    <fg=white>" . implode(', ', $this->aliases) . "</>\n");
        }
    }
    
    /**
     * Validate value against rules
     */
    protected function validate($value, $rules = [])
    {
        $errors = [];
        
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $error = $this->validateStringRule($value, $rule);
                if ($error) $errors[] = $error;
            } elseif (is_array($rule) && isset($rule[0])) {
                $error = $this->validateArrayRule($value, $rule);
                if ($error) $errors[] = $error;
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate string rule
     */
    protected function validateStringRule($value, $rule)
    {
        switch ($rule) {
            case 'required':
                return empty($value) ? "Value is required" : null;
            case 'email':
                return !filter_var($value, FILTER_VALIDATE_EMAIL) ? "Must be a valid email address" : null;
            case 'numeric':
                return !is_numeric($value) ? "Must be numeric" : null;
            case 'integer':
                return !filter_var($value, FILTER_VALIDATE_INT) ? "Must be an integer" : null;
            case 'boolean':
                return !in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no']) ? "Must be a boolean (true/false)" : null;
            case 'url':
                return !filter_var($value, FILTER_VALIDATE_URL) ? "Must be a valid URL" : null;
            case 'ip':
                return !filter_var($value, FILTER_VALIDATE_IP) ? "Must be a valid IP address" : null;
            case 'alpha':
                return !ctype_alpha($value) ? "Must contain only letters" : null;
            case 'alpha_num':
                return !ctype_alnum($value) ? "Must contain only letters and numbers" : null;
            default:
                return null;
        }
    }
    
    /**
     * Validate array rule
     */
    protected function validateArrayRule($value, $rule)
    {
        $type = $rule[0];
        $params = array_slice($rule, 1);
        
        switch ($type) {
            case 'min':
                return strlen($value) < $params[0] ? "Minimum length is {$params[0]} characters" : null;
            case 'max':
                return strlen($value) > $params[0] ? "Maximum length is {$params[0]} characters" : null;
            case 'between':
                return ($value < $params[0] || $value > $params[1]) ? "Must be between {$params[0]} and {$params[1]}" : null;
            case 'in':
                return !in_array($value, $params[0]) ? "Must be one of: " . implode(', ', $params[0]) : null;
            case 'regex':
                return !preg_match($params[0], $value) ? "Invalid format" : null;
            default:
                return null;
        }
    }
}