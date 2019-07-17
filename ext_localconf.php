<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\SourceBroker\Ip2geo\Scheduler\DownloadDatabase::class] = [
    'extension' => $_EXTKEY,
    'title' => 'Download geolocation database',
    'description' => 'Download external database (f.e. MaxMind GeoIP2)',
    'additionalFields' => \SourceBroker\Ip2geo\Scheduler\DownloadAdditionalFieldsProvider::class
];
