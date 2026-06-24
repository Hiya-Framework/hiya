<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */


namespace Hiya\Console;

use CConsoleApplication;
use ReflectionClass;

class Application extends CConsoleApplication
{
    public $name = 'Hiya CLI';
    public $version = HIYA_VERSION;
    public $commandMap = [];
    private $_commandRunner;
    
    public function __construct($config = null)
    {
        if ($config === null) {
            $config = [
                'basePath' => \Yii::getPathOfAlias('application'),
                'name' => $this->name,
            ];
        }
        
        parent::__construct($config);
        
        ob_start();
        $this->registerCommands();
        $this->configureCommands();
        ob_end_clean();
    }
    
    public function init()
    {
        parent::init();
    }
    
    protected function registerCommands()
    {
        $commandsPath = \Yii::getPathOfAlias('application') . '/commands';
        if (!is_dir($commandsPath)) {
            return;
        }
        
        $files = glob($commandsPath . '/*Command.php');
        foreach ($files as $file) {
            $className = basename($file, '.php');
            if (!class_exists($className)) {
                require_once $file;
            }
            
            if (!class_exists($className)) {
                continue;
            }
            
            $reflection = new ReflectionClass($className);
            
            // Accept both CConsoleCommand and Hiya\Console\Command
            if ($reflection->isSubclassOf('CConsoleCommand') || $reflection->isSubclassOf('Hiya\\Console\\Command')) {
                $commandName = $this->getCommandName($className);
                $this->commandMap[$commandName] = [
                    'class' => $className,
                    'name' => $commandName,
                    'description' => $this->getCommandDescription($className),
                    'icon' => $this->getCommandIcon($className),
                    'group' => $this->getCommandGroup($className, $commandName)
                ];
                
                // Get aliases if method exists
                if (method_exists($className, 'getAliases')) {
                    $temp = new $className('temp', null);
                    foreach ($temp->getAliases() as $alias) {
                        $this->commandMap[$alias] = [
                            'class' => $className,
                            'name' => $alias,
                            'description' => $this->getCommandDescription($className),
                            'icon' => $this->getCommandIcon($className),
                            'group' => $this->getCommandGroup($className, $alias)
                        ];
                    }
                }
            }
        }
    }
    
    protected function getCommandName($className)
    {
        if (property_exists($className, 'name')) {
            $props = (new ReflectionClass($className))->getDefaultProperties();
            if (!empty($props['name'])) {
                return $props['name'];
            }
        }
        
        $name = strtolower(str_replace('Command', '', $className));
        return preg_replace('/([a-z])([A-Z])/', '$1:$2', $name);
    }
    
    protected function getCommandDescription($className)
    {
        if (property_exists($className, 'description')) {
            $props = (new ReflectionClass($className))->getDefaultProperties();
            if (!empty($props['description'])) {
                return $props['description'];
            }
        }
        return '';
    }
    
    /**
     * Check if terminal supports Unicode/emoji
     * 
     * @return bool
     */
    protected function supportsUnicode()
    {
        // Check if running on Windows without UTF-8 support
        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows 10 version 1803+ supports emoji
            $version = php_uname('r');
            if (version_compare($version, '10.0.17134', '<')) {
                return false;
            }
            // Check console code page
            $codePage = exec('chcp 2>nul');
            if ($codePage && strpos($codePage, '65001') === false) {
                return false;
            }
            return true;
        }
        
        // Check if terminal supports UTF-8
        $term = getenv('TERM');
        if ($term && in_array($term, ['dumb', 'unknown'])) {
            return false;
        }
        
        return true;
    }

    /**
     * Get safe icon (fallback to ASCII if emoji not supported)
     * 
     * @param string $emoji
     * @param string $ascii
     * @return string
     */
    protected function getSafeIcon($emoji, $ascii)
    {
        return $this->supportsUnicode() ? $emoji : $ascii;
    }

    /**
     * Get default icons (ASCII only for maximum compatibility)
     * 
     * @return array
     */
    protected function getDefaultIcons()
    {
        return [
            'queue' => '[Q]',
            'batch' => '[B]',
            'download' => '[D]',
            'progress' => '[P]',
            'form' => '[F]',
            'survey' => '[S]',
            'cache' => '[C]',
            'migrate' => '[M]',
            'help' => '[?]',
            'queue:work' => '[W]',
            'queue:list' => '[L]',
            'queue:stats' => '[S]',
        ];
    }

    /**
     * Get command icon
     */
    protected function getCommandIcon($className)
    {
        if (property_exists($className, 'icon')) {
            $props = (new ReflectionClass($className))->getDefaultProperties();
            if (!empty($props['icon'])) {
                return $props['icon'];
            }
        }
        
        $commandName = $this->getCommandName($className);
        $baseName = explode(':', $commandName)[0];
        
        $defaultIcons = $this->getDefaultIcons();
        
        // Return mapped icon or default ASCII
        return $defaultIcons[$baseName] ?? '[*]';
    }

    /**
     * Get fallback ASCII icon for emoji
     * 
     * @param string $emoji
     * @return string
     */
    protected function getFallbackIcon($emoji)
    {
        $fallbacks = [
            '📦' => '[Pkg]',  // Package/Queue
            '⚙️' => '[Set]',  // Settings/Batch
            '📥' => '[Dn]',   // Download
            '📊' => '[Pgr]',  // Progress
            '📝' => '[Frm]',  // Form
            '📋' => '[Srv]',  // Survey
            '🗄️' => '[Cch]',  // Cache
            '🗃️' => '[Mig]',  // Migrate
            '❓' => '[Hlp]',  // Help
            '🔹' => '[*]',    // Default
        ];
        
        return $fallbacks[$emoji] ?? '[*]';
    }
    
    protected function getCommandGroup($className, $commandName)
    {
        if (property_exists($className, 'group')) {
            $props = (new ReflectionClass($className))->getDefaultProperties();
            if (!empty($props['group'])) {
                return $props['group'];
            }
        }
        
        if (strpos($commandName, ':') !== false) {
            return explode(':', $commandName)[0];
        }
        
        $groups = [
            'batch' => 'batch', 'download' => 'download', 'progress' => 'progress',
            'form' => 'form', 'survey' => 'form', 'pb' => 'progress',
            'bp' => 'batch', 'dl' => 'download',
        ];
        
        return $groups[$commandName] ?? 'general';
    }
    
    protected function configureCommands()
    {
        $this->_commandRunner = new \CConsoleCommandRunner();

        // Only add commands from commandMap
        foreach ($this->commandMap as $name => $cfg) {
            if (isset($cfg['class'])) {
                $this->_commandRunner->commands[$name] = $cfg['class'];
            }
        }
    }
    
    public function getCommandRunner()
    {
        return $this->_commandRunner;
    }
    
    public function processRequest()
    {
        global $argv;

        $originalArgv = $argv;
        
        $scriptName = array_shift($argv);
        
        if (empty($argv)) {
            $this->showHelp();
            return;
        }
        
        $commandName = $argv[0];
        
        // Show version
        if ($commandName === '--version' || $commandName === '-V') {
            $this->showVersion();
            return;
        }
        
        // Show help
        if ($commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return;
        }
        
        // Check if command exists
        if (!isset($this->_commandRunner->commands[$commandName])) {
            echo $this->colorize("\n  ✗ Command '{$commandName}' not found\n", 'red');
            echo $this->colorize("  Run 'php hiya' to see available commands\n", 'gray');
            return;
        }
        
        try {
            // IMPORTANT: Pass the ENTIRE argv array including script name and command
            // CConsoleCommandRunner::run() expects:
            // $args[0] = script name
            // $args[1] = command name
            // $args[2...] = command arguments
            
            // Rebuild argv with script name in the beginning
            $fullArgv = array_merge([$scriptName], $argv);
                        
            // Run dengan full argv
            $exitCode = $this->_commandRunner->run($fullArgv);
            
            if ($exitCode !== 0 && $exitCode !== null) {
                exit($exitCode);
            }
            
        } catch (\CException $e) {
            echo $this->colorize("\n  ✗ " . $e->getMessage(), 'red') . "\n";
            if (YII_DEBUG) {
                echo $this->colorize($e->getTraceAsString(), 'gray') . "\n";
            }
            exit(1);
        }
    }
    
    protected function colorize($text, $color)
    {
        $codes = ['red' => '31', 'green' => '32', 'yellow' => '33', 'blue' => '34', 'cyan' => '36', 'gray' => '90', 'white' => '37'];
        $code = $codes[$color] ?? '37';
        return "\033[{$code}m{$text}\033[0m";
    }
    
    protected function showHelp()
    {
        echo Style::banner('Hiya Framework Console', \Hiya::getVersion());
        echo "\n";
        
        // Group commands
        $groupedCommands = [];
        foreach ($this->commandMap as $name => $cfg) {
            if (!is_array($cfg)) continue;
            
            $group = $cfg['group'] ?? 'general';
            $description = $cfg['description'] ?? '';
            $groupedCommands[$group][$name] = $description;
        }
        
        // Display commands by group
        foreach ($groupedCommands as $group => $commands) {
            echo Style::heading(ucfirst($group), 2) . "\n";
            echo Style::color(str_repeat('─', 50), 'GRAY') . "\n";
            
            foreach ($commands as $name => $description) {
                $padded = str_pad($name, 20);
                echo "  " . Style::color($padded, 'CYAN') . "  " . $description . "\n";
            }
            echo "\n";
        }
        
        // Usage
        echo Style::heading('Usage', 2) . "\n";
        echo "  " . Style::color('php hiya <command>', 'CYAN') . " [options] [arguments]\n\n";
        
        // Examples
        echo Style::heading('Examples', 2) . "\n";
        echo "  " . Style::color('php hiya batch --items=100', 'CYAN') . "    Process 100 items\n";
        echo "  " . Style::color('php hiya progress', 'CYAN') . "              Run interactive demo\n\n";
    }
    
    protected function showVersion()
    {
        echo Style::info("Hiya Framework v" . \Hiya::getVersion()) . "\n";
        echo Style::info("PHP v" . PHP_VERSION) . "\n";
    }
}