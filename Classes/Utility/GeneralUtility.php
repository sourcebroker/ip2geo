<?php

namespace SourceBroker\Ip2geo\Utility;

use Exception;

/**
 * Class GeneralUtility
 * @package SourceBroker\Ip2geo\Utility
 */
class GeneralUtility
{
    /**
     * @param string $databaseName
     * @return bool|string
     * @throws Exception
     */
    public static function getHash(string $databaseName): string
    {
        $encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        if (empty($encryptionKey)) {
            throw new Exception('EncryptionKey not found.');
        }
        return hash_hmac('sha1', $databaseName, $encryptionKey);
    }
}
