<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Ip2geo Extension',
	'description' => 'Return IP based on geoip. Using maxmind database (free/commercial)',
	'category' => 'plugin',
    'author' => 'SourceBroker Team',
    'author_email' => 'office@sourcebroker.dev',
    'author_company' => 'SourceBroker',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '1',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '0.5.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6.0-9.5.999',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);
