<?php

namespace SourceBroker\Ip2geo\Adapter;

use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class AbstractAdapter
 * @package SourceBroker\Ip2geo\Adapter
 */
abstract class AbstractAdapter
{
    /**
     * Current IP address.
     *
     * @var string
     */
    protected $ip = null;

    /**
     * Get an adapter instance.
     *
     * @param string $databaseName Database name
     * @param string $ip IP address
     *
     * @return $this
     * @throws Exception
     */
    public static function getInstance($databaseName, $ip = null)
    {
        static $instance = null;

        if ($instance !== null) {
            return $instance;
        }

        if ($ip === null) {
            $ip = self::getRequestIP();
        }

        $instance = GeoIp::getInstance($databaseName, $ip);

        if ($instance !== null) {
            return $instance;
        }

        throw new Exception(
            'No installed geoip adapter found'
        );
    }

    /**
     * Get two-letter continent code.
     *
     * @return string|false Continent code or FALSE on failure
     */
    abstract public function getContinentCode();

    /**
     * Get two or three letter country code.
     *
     * @return string|false Country code or FALSE on failure
     */
    abstract public function getCountryCode();

    /**
     * Get country name.
     *
     * @return string|false Country name or FALSE on failure
     */
    abstract public function getCountryName();

    /**
     * Get location record.
     *
     * @return array|false Location data or FALSE on failure
     */
    abstract public function getLocation();

    /**
     * @return string
     * @throws Exception
     */
    protected static function getRequestIP(): string
    {
        $ipAddress = null;
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ip2geo'];
        if (!empty($config)) {
            if (!empty($config['fakeIpHeaderName']) && isset($_SERVER['HTTP_' . $config['fakeIpHeaderName']])) {
                $ipAddress = $_SERVER['HTTP_' . $config['fakeIpHeaderName']];
            } else {
                $ipAddress = GeneralUtility::getIndpEnv('REMOTE_ADDR');
            }
            if ($ipAddress === '127.0.0.1') {
                if (!empty($config['defaultLocalIP'])) {
                    $ipAddress = $config['defaultLocalIP'];
                }
            }
        } else {
            throw new Exception('Can not read ip2geo config.');
        }
        if ($ipAddress === null) {
            throw new Exception('Can not read request IP.');
        }
        return $ipAddress;
    }
}
