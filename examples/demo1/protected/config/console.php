<?php
return array(
    'basePath' => dirname(__FILE__) . '/..',
    'name' => 'Hiya Console',
    'import' => array(
        'application.commands.*',
    ),
    
    'commandMap' => array(
        // Queue commands
        'queue' => array('class' => 'QueueCommand'),
        'queue:stats' => array('class' => 'QueueCommand'),
        'queue:work' => array('class' => 'QueueCommand'),
        'queue:push' => array('class' => 'QueueCommand'),
        'queue:list' => array('class' => 'QueueCommand'),
        'queue:info' => array('class' => 'QueueCommand'),
        
        // Progress commands
        'progress' => array('class' => 'ProgressCommand'),
        'progress-bar' => array('class' => 'ProgressCommand'),

        'batch' => array('class' => 'BatchCommand'),

        'form' => array('class' => 'FormCommand'),
        'survey' => array('class' => 'FormCommand'),
        
    ),
    
    'components' => array(
        'queue' => array(
            'class' => 'Hiya\\Component\\QueueComponent',
            'default' => 'file',
            'connections' => array(
                'file' => array(
                    'path' => dirname(__FILE__) . '/../runtime/queue',
                ),
            ),
        ),
    ),
);