<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Helpers
 * @since 1.0
 */

namespace Hiya\Helpers;

class StringHelper
{
    /**
     * Generate random string
     * 
     * @param int $length
     * @param string $characters
     * @return string
     */
    public static function random($length = 32, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $randomString = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $max)];
        }
        return $randomString;
    }
    
    /**
     * Generate UUID v4
     * 
     * @return string
     */
    public static function uuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    /**
     * Generate slug from string
     * 
     * @param string $text
     * @param string $separator
     * @return string
     */
    public static function slug($text, $separator = '-')
    {
        $text = preg_replace('~[^\pL\d]+~u', $separator, $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, $separator);
        $text = preg_replace('~-+~', $separator, $text);
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }
    
    /**
     * Limit string length
     * 
     * @param string $text
     * @param int $limit
     * @param string $end
     * @return string
     */
    public static function limit($text, $limit = 100, $end = '...')
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit) . $end;
    }
    
    /**
     * Truncate with words
     * 
     * @param string $text
     * @param int $words
     * @param string $end
     * @return string
     */
    public static function words($text, $words = 20, $end = '...')
    {
        $wordsArray = explode(' ', $text);
        if (count($wordsArray) <= $words) {
            return $text;
        }
        return implode(' ', array_slice($wordsArray, 0, $words)) . $end;
    }
    
    /**
     * Camel case to snake case
     * 
     * @param string $text
     * @return string
     */
    public static function snake($text)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $text));
    }
    
    /**
     * Snake case to camel case
     * 
     * @param string $text
     * @return string
     */
    public static function camel($text)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $text))));
    }
    
    /**
     * Snake case to title case
     * 
     * @param string $text
     * @return string
     */
    public static function title($text)
    {
        return ucwords(str_replace('_', ' ', $text));
    }
    
    /**
     * Check if string contains substring
     * 
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
    
    /**
     * Check if string starts with substring
     * 
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return strpos($haystack, $needle) === 0;
    }
    
    /**
     * Check if string ends with substring
     * 
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }
    
    /**
     * Mask string (for passwords, emails, etc.)
     * 
     * @param string $text
     * @param int $visible
     * @param string $mask
     * @return string
     */
    public static function mask($text, $visible = 2, $mask = '*')
    {
        $length = strlen($text);
        if ($length <= $visible * 2) {
            return $text;
        }
        $visibleStart = substr($text, 0, $visible);
        $visibleEnd = substr($text, -$visible);
        $middle = str_repeat($mask, $length - ($visible * 2));
        return $visibleStart . $middle . $visibleEnd;
    }
    
    /**
     * Email mask
     * 
     * @param string $email
     * @return string
     */
    public static function maskEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) < 2) {
            return self::mask($email);
        }
        $username = $parts[0];
        $domain = $parts[1];
        $maskedUsername = self::mask($username);
        return $maskedUsername . '@' . $domain;
    }
    
    /**
     * Generate random string for URL safe
     * 
     * @param int $length
     * @return string
     */
    public static function randomUrl($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
}