<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Ip2geo Extension',
    'description' => 'Returns geo data based on IP - uses Maxmind databases (free/commercial)',
    'category' => 'plugin',
    'author' => 'SourceBroker Team',
    'author_email' => 'office@sourcebroker.dev',
    'author_company' => 'SourceBroker',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '1',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-9.5.999',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
