<?php
/*
 * @author Hermans <github.com/hermans>
 * @copyright (c) taktikspace.com
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Console\Table
 * @since 1.0
 */

namespace Hiya\Console;

require_once __DIR__ . '/_lib/CliTable.php';
require_once __DIR__ . '/_lib/CliTableManipulator.php';

use jc21\CliTable as JCliTable;
use jc21\CliTableManipulator;

class Table
{
    /**
     * @var JCliTable CliTable instance
     */
    protected $table;
    
    /**
     * @var array Fields configuration
     */
    protected $fields = [];
    
    /**
     * @var array Data to display
     */
    protected $data = [];
    
    /**
     * @var string Item name for empty message
     */
    protected $itemName = 'Row';
    
    /**
     * @var string Title for the table
     */
    protected $title = '';
    
    /**
     * Constructor
     */
    public function __construct($itemName = 'Row', $useColors = true, $centerContent = false)
    {
        $this->itemName = $itemName;
        $this->table = new JCliTable($itemName, $useColors, $centerContent);
        $this->table->setTableColor('cyan');
        $this->table->setHeaderColor('yellow');
    }
    
    /**
     * Set table title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    
    /**
     * Set table color
     */
    public function setTableColor($color)
    {
        $this->table->setTableColor($color);
        return $this;
    }
    
    /**
     * Set header color
     */
    public function setHeaderColor($color)
    {
        $this->table->setHeaderColor($color);
        return $this;
    }
    
    /**
     * Set whether to use colors
     */
    public function setUseColors($bool)
    {
        $this->table->setUseColors($bool);
        return $this;
    }
    
    /**
     * Set whether to center content
     */
    public function setCenterContent($bool)
    {
        $this->table->setCenterContent($bool);
        return $this;
    }
    
    /**
     * Set show headers
     */
    public function setShowHeaders($bool)
    {
        $this->table->setShowHeaders($bool);
        return $this;
    }
    
    /**
     * Add a field/column to the table
     * 
     * @param string $name Column header name
     * @param string $key Data key in the array
     * @param string|Closure|null $manipulator Manipulator type or callback
     * @param string $color Color for this column
     */
    public function addField($name, $key, $manipulator = null, $color = 'white')
    {
        $manipulatorObj = null;
        
        if (is_string($manipulator)) {
            $manipulatorObj = new CliTableManipulator($manipulator);
        } elseif (is_callable($manipulator)) {
            $manipulatorObj = new CliTableManipulator('custom');
            $this->fields[$key]['callback'] = $manipulator;
        }
        
        $this->fields[$key] = [
            'name' => $name,
            'key' => $key,
            'manipulator' => $manipulatorObj,
            'color' => $color,
            'callback' => $manipulatorObj ? null : $manipulator
        ];
        
        $this->table->addField($name, $key, $manipulatorObj, $color);
        return $this;
    }
    
    /**
     * Add fields for 3-column layout (Command, Params, Description)
     */
    public function addHelpFields()
    {
        $this->addField('COMMAND', 'command', false, 'cyan');
        $this->addField('PARAMS', 'params', false, 'yellow');
        $this->addField('DESCRIPTION', 'description', false, 'white');
        return $this;
    }
    
    /**
     * Inject data into table
     */
    public function setData(array $data)
    {
        $this->data = $data;
        
        if (!empty($this->fields)) {
            $processedData = [];
            foreach ($data as $row) {
                $processedRow = [];
                foreach ($this->fields as $key => $field) {
                    $value = $row[$key] ?? '';
                    if (isset($field['callback']) && is_callable($field['callback'])) {
                        $value = $field['callback']($value, $row);
                    }
                    $processedRow[$key] = $value;
                }
                $processedData[] = $processedRow;
            }
            $this->table->injectData($processedData);
        } else {
            $this->table->injectData($data);
        }
        
        return $this;
    }
    
    /**
     * Add a single row
     */
    public function addRow(array $row)
    {
        $this->data[] = $row;
        $this->table->injectData($this->data);
        return $this;
    }
    
    /**
     * Add a help row (command, params, description)
     */
    public function addHelpRow($command, $params, $description)
    {
        $this->addRow([
            'command' => $command,
            'params' => $params,
            'description' => $description
        ]);
        return $this;
    }
    
    /**
     * Set headers directly (alternative to addField)
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $index => $header) {
            $key = 'col_' . $index;
            $this->addField($header, $key);
        }
        return $this;
    }
    
    /**
     * Set rows directly (for indexed arrays)
     */
    public function setRows(array $rows)
    {
        $assocRows = [];
        foreach ($rows as $row) {
            $assocRow = [];
            foreach ($row as $i => $value) {
                $assocRow['col_' . $i] = $value;
            }
            $assocRows[] = $assocRow;
        }
        return $this->setData($assocRows);
    }
    
    /**
     * Render the table with title
     */
    public function render()
    {
        // Render title if set
        if (!empty($this->title)) {
            $this->renderTitle();
        }
        
        $this->table->display();
    }
    
    /**
     * Render title using CliTable
     */
    protected function renderTitle()
    {
        // Get table width from first field
        $width = 0;
        $tempTable = new JCliTable($this->itemName, true, false);
        
        // Create a temporary table to calculate width
        $tempTable->addField('TEMP', 'temp', false, 'white');
        $tempData = [['temp' => str_repeat(' ', 60)]];
        $tempTable->injectData($tempData);
        $tempOutput = $tempTable->get();
        
        // Extract width from output
        $lines = explode("\n", $tempOutput);
        if (isset($lines[0])) {
            $width = strlen($lines[0]) - 2;
        }
        
        $titleText = " " . $this->title . " ";
        $padding = ($width - strlen($titleText)) / 2;
        
        echo "\033[36m" . str_repeat("─", $width) . "\033[0m\n";
        echo "\033[36m│\033[0m" . str_repeat(" ", (int)$padding) . "\033[33m" . $titleText . "\033[0m" . str_repeat(" ", (int)ceil($padding)) . "\033[36m│\033[0m\n";
        echo "\033[36m" . str_repeat("─", $width) . "\033[0m\n";
    }
    
    /**
     * Get table as string
     */
    public function get()
    {
        ob_start();
        $this->render();
        return ob_get_clean();
    }
    
    /**
     * Magic method to string
     */
    public function __toString()
    {
        return $this->get();
    }
}