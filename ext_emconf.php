<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Events2 Reserve Connector',
    'description' => 'This extension provides extended functionalities for integrating Events2 and Reserve extension.',
    'category' => 'plugin',
    'author' => 'Hoja Mustaffa Abdul Latheef',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.8-12.4.99',
            'events2' => '9.0.0-0.0.0',
        ],
        'conflicts' => [
        ],
    ],
];
