<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Console\Style
 * @since 1.0
 */

namespace Hiya\Console;

class Style
{
    // Text colors
    const BLACK = '0;30';
    const DARK_GRAY = '1;30';
    const RED = '0;31';
    const LIGHT_RED = '1;31';
    const GREEN = '0;32';
    const LIGHT_GREEN = '1;32';
    const BROWN = '0;33';
    const YELLOW = '1;33';
    const BLUE = '0;34';
    const LIGHT_BLUE = '1;34';
    const PURPLE = '0;35';
    const LIGHT_PURPLE = '1;35';
    const CYAN = '0;36';
    const LIGHT_CYAN = '1;36';
    const LIGHT_GRAY = '0;37';
    const WHITE = '1;37';
    
    // Background colors
    const BG_BLACK = '40';
    const BG_RED = '41';
    const BG_GREEN = '42';
    const BG_YELLOW = '43';
    const BG_BLUE = '44';
    const BG_MAGENTA = '45';
    const BG_CYAN = '46';
    const BG_LIGHT_GRAY = '47';
    
    // Text styles
    const BOLD = '1';
    const DIM = '2';
    const ITALIC = '3';
    const UNDERLINE = '4';
    const BLINK = '5';
    const REVERSE = '7';
    const HIDDEN = '8';
    
    // Icons with ASCII fallback
    private static $icons = [];
    private static $useEmoji = null;
    
    /**
     * Initialize style defaults (called automatically)
     */
    public static function init()
    {
        if (self::$useEmoji === null) {
            self::$useEmoji = self::supportsEmoji();
            self::loadIcons();
        }
    }
    
    /**
     * Check if terminal supports emoji
     * 
     * @return bool
     */
    public static function supportsEmoji()
    {
        // Windows without UTF-8
        if (DIRECTORY_SEPARATOR === '\\') {
            $version = php_uname('r');
            if (version_compare($version, '10.0.17134', '<')) {
                return false;
            }
            $codePage = exec('chcp 2>nul');
            if ($codePage && strpos($codePage, '65001') === false) {
                return false;
            }
            return true;
        }
        
        $term = getenv('TERM');
        if ($term && in_array($term, ['dumb', 'unknown'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Load icon sets based on emoji support
     */
    private static function loadIcons()
    {
        if (self::$useEmoji) {
            self::$icons = [
                'success' => '✓',
                'error' => '✗',
                'warning' => '⚠',
                'info' => 'ℹ',
                'question' => '?',
                'arrow' => '→',
                'bullet' => '•',
                'check' => '✔',
                'cross' => '✘',
                'star' => '★',
                'heart' => '♥',
                'folder' => '📁',
                'file' => '📄',
                'search' => '🔍',
                'settings' => '⚙',
                'download' => '⬇',
                'upload' => '⬆',
                'time' => '⏱',
                'calendar' => '📅',
                'user' => '👤',
                'lock' => '🔒',
                'unlock' => '🔓',
                'package' => '📦',
                'batch' => '⚙️',
                'progress' => '📊',
                'form' => '📝',
                'survey' => '📋',
                'cache' => '🗄️',
                'migrate' => '🗃️',
            ];
        } else {
            self::$icons = [
                'success' => '[✓]',
                'error' => '[✗]',
                'warning' => '[!]',
                'info' => '[i]',
                'question' => '[?]',
                'arrow' => '->',
                'bullet' => '*',
                'check' => '[OK]',
                'cross' => '[FAIL]',
                'star' => '*',
                'heart' => '<3',
                'folder' => '[DIR]',
                'file' => '[FILE]',
                'search' => '[SEARCH]',
                'settings' => '[SET]',
                'download' => '[DL]',
                'upload' => '[UL]',
                'time' => '[TIME]',
                'calendar' => '[DATE]',
                'user' => '[USER]',
                'lock' => '[LOCK]',
                'unlock' => '[UNLOCK]',
                'package' => '[PKG]',
                'batch' => '[BCH]',
                'progress' => '[PROG]',
                'form' => '[FRM]',
                'survey' => '[SRV]',
                'cache' => '[CCH]',
                'migrate' => '[MIG]',
            ];
        }
    }
    
    /**
     * Get icon by key
     * 
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function icon($key, $default = '•')
    {
        self::init();
        return self::$icons[$key] ?? $default;
    }
    
    /**
     * Apply style to text
     * 
     * @param string $text
     * @param string|array $styles
     * @return string
     */
    public static function apply($text, $styles = [])
    {
        if (empty($styles)) {
            return $text;
        }
        
        if (!is_array($styles)) {
            $styles = [$styles];
        }
        
        $codes = [];
        foreach ($styles as $style) {
            $styleUpper = strtoupper($style);
            if (defined("self::$styleUpper")) {
                $codes[] = constant("self::$styleUpper");
            } elseif (defined("self::BG_" . $styleUpper)) {
                $codes[] = constant("self::BG_" . $styleUpper);
            }
        }
        
        if (empty($codes)) {
            return $text;
        }
        
        $code = implode(';', $codes);
        return "\033[{$code}m{$text}\033[0m";
    }
    
    /**
     * Colorize text with simple color name
     * 
     * @param string $text
     * @param string $color
     * @param array $options
     * @return string
     */
    public static function color($text, $color, $options = [])
    {
        $styles = [strtoupper($color)];
        $styles = array_merge($styles, array_map('strtoupper', $options));
        return self::apply($text, $styles);
    }
    
    /**
     * Format text as heading
     * 
     * @param string $text
     * @param int $level
     * @return string
     */
    public static function heading($text, $level = 1)
    {
        $prefix = $level === 1 ? self::icon('star') . ' ' : '  • ';
        return self::color($prefix . $text, 'CYAN', ['bold']);
    }
    
    /**
     * Format text as success message
     * 
     * @param string $text
     * @return string
     */
    public static function success($text)
    {
        return self::color(self::icon('success') . ' ' . $text, 'GREEN');
    }
    
    /**
     * Format text as error message
     * 
     * @param string $text
     * @return string
     */
    public static function error($text)
    {
        return self::color(self::icon('error') . ' ' . $text, 'RED');
    }
    
    /**
     * Format text as warning message
     * 
     * @param string $text
     * @return string
     */
    public static function warning($text)
    {
        return self::color(self::icon('warning') . ' ' . $text, 'YELLOW');
    }
    
    /**
     * Format text as info message
     * 
     * @param string $text
     * @return string
     */
    public static function info($text)
    {
        return self::color(self::icon('info') . ' ' . $text, 'CYAN');
    }
    
    /**
     * Format text as muted/comment
     * 
     * @param string $text
     * @return string
     */
    public static function comment($text)
    {
        return self::color('// ' . $text, 'DARK_GRAY');
    }
    
    /**
     * Create a progress bar style
     * 
     * @param int $current
     * @param int $total
     * @param int $width
     * @return string
     */
    public static function progressBar($current, $total, $width = 50)
    {
        $percent = $total > 0 ? round($current / $total * 100) : 0;
        $filled = round($width * $current / $total);
        $bar = str_repeat('=', $filled) . str_repeat(' ', $width - $filled);
        
        return self::color($bar, 'GREEN') . ' ' . self::color($percent . '%', 'YELLOW');
    }
    
    /**
     * Create a table row style
     * 
     * @param array $columns
     * @param array $widths
     * @return string
     */
    public static function tableRow($columns, $widths = [])
    {
        $row = '';
        foreach ($columns as $i => $col) {
            $width = $widths[$i] ?? 20;
            $row .= self::color(str_pad($col, $width), 'WHITE');
        }
        return $row;
    }
    
    /**
     * Get banner
     * 
     * @param string $title
     * @param string $version
     * @param array $params Additional parameters (width, color, etc.)
     * @return string
     */
    public static function banner($title, $version = '', $params = [])
    {
        $defaults = [
            'width' => 60,
            'color' => 'CYAN',
            'border_char' => '═',
        ];
        
        $params = array_merge($defaults, $params);
        $width = $params['width'];
        $color = $params['color'];
        $borderChar = $params['border_char'];
        
        $titleText = " {$title} ";
        if ($version) {
            $titleText .= "v{$version} ";
        }
        
        $line = self::color(str_repeat($borderChar, $width), $color);
        $titleLine = self::color(str_pad($titleText, $width, ' ', STR_PAD_BOTH), $color, ['bold']);
        
        return "\n{$line}\n{$titleLine}\n{$line}\n";
    }
    
    /**
     * Clear screen
     * 
     * @return string
     */
    public static function clearScreen()
    {
        return "\033[2J\033[;H";
    }
    
    /**
     * Move cursor up
     * 
     * @param int $lines
     * @return string
     */
    public static function cursorUp($lines = 1)
    {
        return "\033[{$lines}A";
    }
    
    /**
     * Move cursor down
     * 
     * @param int $lines
     * @return string
     */
    public static function cursorDown($lines = 1)
    {
        return "\033[{$lines}B";
    }
    
    /**
     * Save cursor position
     * 
     * @return string
     */
    public static function saveCursor()
    {
        return "\033[s";
    }
    
    /**
     * Restore cursor position
     * 
     * @return string
     */
    public static function restoreCursor()
    {
        return "\033[u";
    }
}

// Initialize on file load
Style::init();