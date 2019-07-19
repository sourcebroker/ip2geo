<?php
namespace SourceBroker\Ip2geo\Utility;

class GeneralUtility
{
    public static function getHash() {
        $encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];

        if (!$encryptionKey) {
            throw new Exception('EncryptionKey not found.');
        }

        return substr($encryptionKey,25,10);
    }
}