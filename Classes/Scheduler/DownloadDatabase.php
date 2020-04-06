<?php

namespace SourceBroker\Ip2geo\Scheduler;

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Class DownloadDatabase
 * @package SourceBroker\Ip2geo\Scheduler
 */
class DownloadDatabase extends AbstractTask
{
    /**
     *
     */
    const WORKING_ROOT_PATH = 'uploads/tx_ip2geo/';

    /**
     * Download url
     * Example for free database: http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz
     * Example for free database: http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz
     * Example for commercial database: https://www.maxmind.com/app/geoip_download?edition_id=PRODUCT_ID&suffix=tar.gz&license_key=LICENCE_KEY
     *
     * @var string
     */
    protected $downloadUrl;

    /**
     * @var string
     */
    protected $databaseName;

    /**
     * Database path
     *
     * @var string
     */
    private $databasePath;

    /**
     * Temporary database directory path
     *
     * @var string
     */
    private $tempDatabasePath;

    /**
     * Temporary package file path
     *
     * @var string
     */
    private $tempDownloadPath;

    /**
     * @var string[]
     */
    private $allowedExts = ['.tar.gz', '.tar'];

    /**
     * @var string
     */
    private $ext = null;

    /**
     * @var bool
     */
    private $isCommercial = false;

    /**
     * This is the main method that is called when a task is executed
     * It MUST be implemented by all classes inheriting from this one
     * Note that there is no error handling, errors and failures are expected
     * to be handled and logged by the client implementations.
     * Should return TRUE on successful execution, FALSE on error.
     *
     * @return boolean Returns TRUE on successful execution, FALSE on error
     * @throws Exception
     */
    public function execute()
    {
        $this->ext = $this->getExtension();
        if (!$this->ext) {
            $this->showMessage('File extension of given file download URL is not allowed.', 'Execution', 'ERROR');
            return false;
        }

        $this->databasePath = self::WORKING_ROOT_PATH . $this->databaseName . DIRECTORY_SEPARATOR;
        $this->tempDatabasePath = $this->databasePath . 'temp/';
        $this->tempDownloadPath = $this->tempDatabasePath . 'download' . $this->ext;

        $this->databasePath = GeneralUtility::getFileAbsFileName($this->databasePath);
        $this->tempDownloadPath = GeneralUtility::getFileAbsFileName($this->tempDownloadPath);
        $this->tempDatabasePath = GeneralUtility::getFileAbsFileName($this->tempDatabasePath);

        if ($this->getDatabaseName() == '') {
            $this->showMessage('Database name is not defined.', 'Execution', 'ERROR');
            return false;
        }

        $inited = $this->initializeDirectories();
        if (!$inited) {
            $this->showMessage('Unable to initialize directories.', 'Execution', 'ERROR');
            return false;
        }

        $downloaded = $this->downloadPackage();
        if (!$downloaded) {
            $this->showMessage('Unable to download package.', 'Execution', 'ERROR');
            return false;
        }

        $unpacked = $this->extractPackage();
        if (!$unpacked) {
            $this->showMessage('Unable to extract package.', 'Execution', 'ERROR');
            return false;
        }

        $installed = $this->installDatabase();
        if (!$installed) {
            $this->showMessage('Unable to install databse.', 'Execution', 'ERROR');
            return false;
        }

        $this->cleanTemp();
        $this->removeOlderDatabase();

        $this->showMessage('Database has been properly installed.', 'Execution', 'OK');
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function initializeDirectories()
    {
        $workingRootPath = GeneralUtility::getFileAbsFileName(self::WORKING_ROOT_PATH);
        if (!is_dir($workingRootPath)) {
            $result = GeneralUtility::mkdir($workingRootPath);
            if (!$result) {
                $this->showMessage('Unable to create working root directory.', 'Init', 'ERROR');
                return false;
            }
        }

        if (!is_dir($this->databasePath)) {
            $result = GeneralUtility::mkdir($this->databasePath);
            if (!$result) {
                $this->showMessage('Unable to create database directory.', 'Init', 'ERROR');
                return false;
            }
        }

        if (!is_dir($this->tempDatabasePath)) {
            $result = GeneralUtility::mkdir($this->tempDatabasePath);
            if (!$result) {
                $this->showMessage('Unable to create temporary directory.', 'Init', 'ERROR');
                return false;
            }
        }

        return true;
    }

    /**
     * Download database
     *
     * @return bool
     * @throws Exception
     */
    protected function downloadPackage()
    {
        set_time_limit(0);

        $fp = fopen($this->tempDownloadPath, 'w');

        $ch = curl_init(str_replace(' ', '%20', $this->getDownloadUrl()));
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($fp);

        if ($httpCode != 200 || !file_exists($this->tempDownloadPath) || filesize($this->tempDownloadPath) == 0) {
            $this->showMessage('Failed download database', 'Download', 'ERROR');
            return false;
        }

        return true;
    }

    /**
     * Unpack downloaded package
     *
     * @return bool
     * @throws Exception
     */
    protected function extractPackage()
    {
        $output = null;
        $result = null;
        exec('cd ' . $this->tempDatabasePath . ' && tar -zxf ' . $this->tempDownloadPath, $output, $result);

        if ($result != 0) {
            $this->showMessage('Could not extract package', 'Extract', 'ERROR');
            return false;
        }

        return true;
    }

    /**
     * Install database
     *
     * @return bool
     * @throws Exception
     */
    protected function installDatabase()
    {
        if (!is_dir($this->tempDatabasePath)) {
            $this->showMessage('Extracted directory not exists', 'Install', 'ERROR');
            return false;
        }

        $scan = glob('{' . $this->tempDatabasePath . '*/*.mmdb,' . $this->tempDatabasePath . '*.mmdb}', GLOB_BRACE);
        if (count($scan) == 0) {
            $this->showMessage('Database file not exists in extracted directory', 'Install', 'ERROR');
            return false;
        }

        $databaseFile = $scan[0];
        $targetDatabase = $this->databasePath . date('Ymd-Hi_') . ($this->isCommercial ? 'commercial_' : 'free_')
            . pathinfo(basename($databaseFile), PATHINFO_FILENAME) . "_"
            . \SourceBroker\Ip2geo\Utility\GeneralUtility::getHash($this->getDatabaseName()) . '.'
            . pathinfo(basename($databaseFile), PATHINFO_EXTENSION);

        $copied = copy($databaseFile, $targetDatabase);
        if (!$copied) {
            $this->showMessage('Unable to copy database file', 'Install', 'ERROR');
            return false;
        }

        $targetLink = GeneralUtility::getFileAbsFileName(self::WORKING_ROOT_PATH) . $this->getDatabaseName() . '_'
            . \SourceBroker\Ip2geo\Utility\GeneralUtility::getHash($this->getDatabaseName()) . '.'
            . pathinfo($targetDatabase, PATHINFO_EXTENSION);

        if (file_exists($targetLink)) {
            unlink($targetLink);
        }

        chdir(PATH_site . 'uploads/tx_ip2geo');
        $symlinked = symlink($targetDatabase, pathinfo($targetLink, PATHINFO_BASENAME));
        if (!$symlinked) {
            $this->showMessage('Unable to create symbolic link', 'Install', 'ERROR');
            return false;
        }

        return true;
    }

    /**
     * Clean temp directory
     */
    protected function cleanTemp()
    {
        if (is_dir($this->tempDatabasePath)) {
            if (preg_match('/^' . str_replace('/', '\/', PATH_site) . '/', $this->tempDatabasePath)) {
                GeneralUtility::rmdir($this->tempDatabasePath, true);
            }
        }

        if (file_exists($this->tempDownloadPath)) {
            unlink($this->tempDownloadPath);
        }
    }

    /**
     * Remove older database files
     */
    protected function removeOlderDatabase()
    {
        $toRemove = [];

        if (preg_match('/^' . str_replace('/', '\/', PATH_site) . '/', $this->databasePath)) {
            $files = glob($this->databasePath . '/*.*');

            if (count($files)) {
                foreach ($files as $file) {
                    $toRemove[filectime($file)] = $file;
                }

                ksort($toRemove);
                array_pop($toRemove);
                array_map('unlink', $toRemove);
            }
        }
    }

    /**
     * Show flash message
     *
     * @param string $message
     * @param string $messageTitle
     * @param string $messageType
     * @throws Exception
     */
    protected function showMessage($message, $messageTitle, $messageType = 'INFO')
    {
        if ($messageType === 'ERROR') {
            throw new \RuntimeException(self::class . ' error: ' . $message, 1563719873519);
        }
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
            echo $message . "\n";
        } else {
            $messageType = mb_strtoupper($messageType);
            $messageType = (defined('TYPO3\CMS\Core\Messaging\FlashMessage::' . $messageType))
                ? constant('TYPO3\CMS\Core\Messaging\FlashMessage::' . $messageType)
                : FlashMessage::INFO;

            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $messageTitle, $messageType,
                                                         true);
            /** @var $flashMessageService FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
    }

    /**
     * Get package extension
     */
    protected function getExtension()
    {
        foreach ($this->allowedExts as $ext) {
            $eLen = strlen($ext);
            $part = substr($this->downloadUrl, -$eLen);

            if ($part == $ext) {
                return $ext;
            }
        }

        return null;
    }

    /**
     * Set download url
     *
     * @param string $downloadUrl
     */
    public function setDownloadUrl($downloadUrl)
    {
        $this->downloadUrl = $downloadUrl;
    }

    /**
     * Get download url
     *
     * @return string
     */
    public function getDownloadUrl()
    {
        return $this->downloadUrl;
    }

    /**
     * Set database name
     *
     * @param string $databaseName
     */
    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;
    }

    /**
     * Get database name
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }
}
