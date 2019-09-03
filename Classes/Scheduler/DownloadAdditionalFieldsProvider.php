<?php

namespace SourceBroker\Ip2geo\Scheduler;

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class DownloadAdditionalFieldsProvider implements AdditionalFieldProviderInterface
{
    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param AbstractTask $task The task object being edited. Null when adding a task!
     * @param SchedulerModuleController $parentObject
     * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(
        array &$taskInfo,
        $task,
        SchedulerModuleController $parentObject
    ) {
        if (!$task) {
            /** @var DownloadDatabase $task */
            $task = GeneralUtility::makeInstance(DownloadDatabase::class);
        }

        $additionalFields['databaseName'] = [
            'code' => '<input type="text" class="form-control" name="tx_scheduler[databaseName]" value="' . $task->getDatabaseName() . '" />',
            'label' => 'LLL:EXT:ip2geo/Resources/Private/Language/locallang_be.xlf:scheduler.databaseName',
            'cshKey' => '',
            'cshLabel' => ''
        ];

        $predefinedUrls = [
            'free_country' => 'https://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz',
            'free_city' => 'https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz',
            //'commercial_country' => 'https://{AccountID}:{LicenseKey}@geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz',
            //'commercial_city' => 'https://{AccountID}:{LicenseKey}@geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz',
        ];

        $downloadUrlFieldHtml = '
        <div class="form-wizards-wrap">
            <div class="form-wizards-element">
                <input type="text" name="tx_scheduler[downloadUrl]" class="form-control" id="task_downloadUrl" value="' . $task->getDownloadUrl() . '">
            </div>
            <div class="form-wizards-items-aside">
                <div class="btn-group">
                <select class="form-control tceforms-select tceforms-wizardselect"
                        onchange="document.getElementById(\'task_downloadUrl\').value=this.options[this.selectedIndex].value;this.blur();this.selectedIndex=0;">
                    <option></option>
        ';

        foreach ($predefinedUrls as $name => $url) {
            $translatedLabel = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'LLL:EXT:ip2geo/Resources/Private/Language/locallang_be.xlf:scheduler.predefinedUrl_' . $name,
                'ip2geo'
            );
            $downloadUrlFieldHtml .= '<option value="' . $url . '">' . $translatedLabel . '</option>';
        }
        $downloadUrlFieldHtml .= '
                </select>
                </div>
            </div>
        </div>
        ';

        $additionalFields['downloadUrl'] = [
            'code' => $downloadUrlFieldHtml,
            'label' => 'LLL:EXT:ip2geo/Resources/Private/Language/locallang_be.xlf:scheduler.downloadUrl',
            'cshKey' => '',
            'cshLabel' => ''
        ];

        return $additionalFields;
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param SchedulerModuleController $parentObject
     * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     * @throws Exception
     */
    public function validateAdditionalFields(
        array &$submittedData,
        SchedulerModuleController $parentObject
    ) {
        if ($submittedData['downloadUrl'] == '') {
            $this->showFlashMessage('Downloaded url can\'t be empty', 'Validation', 'ERROR');
            return false;
        }

        if ($submittedData['databaseName'] == '') {
            $this->showFlashMessage('Database name can\'t be empty', 'Validation', 'ERROR');
            return false;
        }

        return true;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param AbstractTask $task Reference to the scheduler backend module
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        /** @var DownloadDatabase $task */
        $task->setDownloadUrl($submittedData['downloadUrl']);
        $task->setDatabaseName($submittedData['databaseName']);
    }

    /**
     * Show flash message
     *
     * @param string $message
     * @param string $messageTitle
     * @param string $messageType
     * @throws Exception
     */
    protected function showFlashMessage($message, $messageTitle, $messageType = 'INFO')
    {
        $messageType = mb_strtoupper($messageType);
        $messageType = (defined('TYPO3\CMS\Core\Messaging\FlashMessage::' . $messageType)) ? constant('TYPO3\CMS\Core\Messaging\FlashMessage::' . $messageType) : FlashMessage::INFO;

        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $messageTitle, $messageType, true);
        /** @var $flashMessageService FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        /** @var $defaultFlashMessageQueue FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }
}
