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
     * @return bool|string
     * @throws Exception
     */
    public static function getHash()
    {
        $encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        if (!$encryptionKey) {
            throw new Exception('EncryptionKey not found.');
        }
        return substr($encryptionKey, 25, 10);
    }
}
