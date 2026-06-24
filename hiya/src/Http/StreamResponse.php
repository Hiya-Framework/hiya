<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Http;

/**
 * Stream Response - For chunked streaming
 */
class StreamResponse extends AbstractResponse
{
    /**
     * @var callable Stream callback
     */
    protected $callback;
    
    /**
     * @var int Chunk size
     */
    protected $chunkSize = 8192;
    
    /**
     * Constructor
     */
    public function __construct($callback, $chunkSize = 8192)
    {
        $this->callback = $callback;
        $this->chunkSize = $chunkSize;
        $this->withHeader('Transfer-Encoding', 'chunked');
        $this->withHeader('Content-Type', 'text/plain');
    }
    
    /**
     * {@inheritdoc}
     */
    protected function sendBody()
    {
        $callback = $this->callback;
        $callback();
    }
    
    /**
     * Create JSON lines stream
     */
    public static function jsonLines($generator)
    {
        $callback = function() use ($generator) {
            foreach ($generator as $item) {
                echo json_encode($item) . "\n";
                ob_flush();
                flush();
            }
        };
        
        $response = new static($callback);
        $response->withHeader('Content-Type', 'application/x-ndjson');
        return $response;
    }
}