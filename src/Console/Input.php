<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Console\Input
 * @since 1.0
 */

namespace Hiya\Console;

class Input
{
    /**
     * @var array Validation rules
     */
    protected $rules = [];
    
    /**
     * @var array Error messages
     */
    protected $errors = [];
    
    /**
     * @var array Default validation rules
     */
    protected $defaultRules = [
        'required' => 'Value is required',
        'email' => 'Must be a valid email address',
        'numeric' => 'Must be numeric',
        'integer' => 'Must be an integer',
        'boolean' => 'Must be true/false',
        'url' => 'Must be a valid URL',
        'ip' => 'Must be a valid IP address',
        'alpha' => 'Must contain only letters',
        'alpha_num' => 'Must contain only letters and numbers',
        'date' => 'Must be a valid date (Y-m-d)',
        'datetime' => 'Must be a valid datetime',
    ];
    
    /**
     * Ask question with validation
     */
    public function ask($question, $rules = [], $default = null, $command = null)
    {
        while (true) {
            $answer = $this->prompt($question, $default, $command);
            $errors = $this->validate($answer, $rules);
            
            if (empty($errors)) {
                return $answer;
            }
            
            $this->displayErrors($errors, $command);
        }
    }
    
    /**
     * Ask for hidden input (password)
     */
    public function secret($question, $rules = [], $command = null)
    {
        while (true) {
            $answer = $this->promptSecret($question, $command);
            $errors = $this->validate($answer, $rules);
            
            if (empty($errors)) {
                return $answer;
            }
            
            $this->displayErrors($errors, $command);
        }
    }
    
    /**
     * Choose from options
     */
    public function choice($question, $options, $default = null, $command = null)
    {
        $this->displayOptions($question, $options, $command);
        
        $rules = ['in' => [array_keys($options)]];
        $answer = $this->ask("Select option", $rules, $default, $command);
        
        return $options[$answer];
    }
    
    /**
     * Confirm action
     */
    public function confirm($question, $default = false, $command = null)
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        $answer = $this->prompt("{$question} ({$defaultText})", null, $command);
        
        if ($default && $answer === '') return true;
        if (!$default && $answer === '') return false;
        
        return in_array(strtolower($answer), ['y', 'yes', 'true', '1']);
    }
    
    /**
     * Validate value against rules
     */
    public function validate($value, $rules = [])
    {
        $this->errors = [];
        
        foreach ($rules as $rule) {
            $this->applyRule($value, $rule);
        }
        
        return $this->errors;
    }
    
    /**
     * Apply single validation rule
     */
    protected function applyRule($value, $rule)
    {
        if (is_string($rule)) {
            $this->applyStringRule($value, $rule);
        } elseif (is_array($rule)) {
            $this->applyArrayRule($value, $rule);
        }
    }
    
    /**
     * Apply string rule (required, email, etc)
     */
    protected function applyStringRule($value, $rule)
    {
        $method = 'validate' . ucfirst($rule);
        
        if (method_exists($this, $method)) {
            if (!$this->$method($value)) {
                $this->errors[] = $this->defaultRules[$rule] ?? "Invalid {$rule}";
            }
        }
    }
    
    /**
     * Apply array rule (min, max, between, in, regex)
     */
    protected function applyArrayRule($value, $rule)
    {
        $type = $rule[0];
        $params = array_slice($rule, 1);
        
        switch ($type) {
            case 'min':
                if (strlen($value) < $params[0]) {
                    $this->errors[] = "Minimum length is {$params[0]} characters";
                }
                break;
            case 'max':
                if (strlen($value) > $params[0]) {
                    $this->errors[] = "Maximum length is {$params[0]} characters";
                }
                break;
            case 'between':
                if ($value < $params[0] || $value > $params[1]) {
                    $this->errors[] = "Must be between {$params[0]} and {$params[1]}";
                }
                break;
            case 'in':
                if (!in_array($value, $params[0])) {
                    $this->errors[] = "Must be one of: " . implode(', ', $params[0]);
                }
                break;
            case 'regex':
                if (!preg_match($params[0], $value)) {
                    $this->errors[] = "Invalid format";
                }
                break;
        }
    }
    
    /**
     * Validation methods
     */
    protected function validateRequired($value)
    {
        return !empty($value);
    }
    
    protected function validateEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    protected function validateNumeric($value)
    {
        return is_numeric($value);
    }
    
    protected function validateInteger($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    protected function validateBoolean($value)
    {
        return in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no']);
    }
    
    protected function validateUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    protected function validateIp($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    
    protected function validateAlpha($value)
    {
        return ctype_alpha($value);
    }
    
    protected function validateAlphaNum($value)
    {
        return ctype_alnum($value);
    }
    
    protected function validateDate($value)
    {
        $date = date_parse($value);
        return $date['error_count'] === 0 && $date['warning_count'] === 0;
    }
    
    protected function validateDatetime($value)
    {
        return strtotime($value) !== false;
    }
    
    /**
     * Prompt user for input
     */
    protected function prompt($question, $default = null, $command = null)
    {
        $display = $question;
        if ($default !== null) {
            $display .= " [{$default}]";
        }
        $display .= ': ';
        
        if ($command) {
            $command->line($display, 'green');
            $answer = trim(fgets(STDIN));
        } else {
            echo $this->colorize($display, 'green');
            $answer = trim(fgets(STDIN));
        }
        
        return $answer ?: $default;
    }
    
    /**
     * Prompt for secret input (password)
     */
    protected function promptSecret($question, $command = null)
    {
        if ($command) {
            $command->line($question . ': ', 'green');
        } else {
            echo $this->colorize($question . ': ', 'green');
        }
        
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
     * Display options for choice
     */
    protected function displayOptions($question, $options, $command = null)
    {
        if ($command) {
            $command->line($question, 'cyan');
            foreach ($options as $key => $option) {
                $command->line("  [{$key}] {$option}", 'gray');
            }
        } else {
            echo $this->colorize($question . "\n", 'cyan');
            foreach ($options as $key => $option) {
                echo $this->colorize("  [{$key}] {$option}\n", 'gray');
            }
        }
    }
    
    /**
     * Display validation errors
     */
    protected function displayErrors($errors, $command = null)
    {
        foreach ($errors as $error) {
            if ($command) {
                $command->error($error);
            } else {
                echo $this->colorize("  ✗ {$error}\n", 'red');
            }
        }
    }
    
    /**
     * Colorize text
     */
    protected function colorize($text, $color)
    {
        $colors = [
            'red' => '31', 'green' => '32', 'yellow' => '33',
            'blue' => '34', 'magenta' => '35', 'cyan' => '36',
            'white' => '37', 'gray' => '90',
        ];
        $code = $colors[$color] ?? '37';
        return "\033[{$code}m{$text}\033[0m";
    }
    
    /**
     * Add custom validation rule
     */
    public function addRule($name, $callback, $message = null)
    {
        $this->defaultRules[$name] = $message ?? "Invalid {$name}";
        $this->rules[$name] = $callback;
        return $this;
    }
    
    /**
     * Custom validation method
     */
    public function validateCustom($value, $ruleName)
    {
        if (isset($this->rules[$ruleName]) && is_callable($this->rules[$ruleName])) {
            return call_user_func($this->rules[$ruleName], $value);
        }
        return true;
    }
}