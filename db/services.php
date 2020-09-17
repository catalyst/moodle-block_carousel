<?php
$functions = [
    'block_carousel_update_slide_order' => [
        'classname' => 'block_carousel\external\external',
        'methodname' => 'process_update_action',
        'description' => 'Process AJAX slide arrangement.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => ['moodle/block:edit'],
    ],
    'block_carousel_record_interaction' => [
        'classname' => 'block_carousel\external\external',
        'methodname' => 'record_interaction',
        'description' => 'Record a user interaction with a slide.',
        'type' => 'update',
        'ajax' => true,
        'capabilities' => [],
    ]
];