<?php

namespace SourceBroker\Ip2geo\Adapter;

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

use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use GeoIp2\Model\Country;
use MaxMind\Db\Reader\InvalidDatabaseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GeoIp
 * @package SourceBroker\Ip2geo\Adapter
 */
class GeoIp extends AbstractAdapter
{
    /**
     * @var Reader;
     */
    protected $reader;

    /**
     * @var Country;
     */
    protected $country = null;

    /**
     * @var City;
     */
    protected $city = null;

    /**
     * @var string
     */
    private $dbPath;

    /**
     * GeoIp constructor
     *
     * @param string $databaseName
     * @param null $ip
     * @throws Exception
     */
    protected function __construct($databaseName, $ip = null)
    {
        if ($ip === null) {
            $this->ip = parent::getRequestIP();
        } else {
            $this->ip = $ip;
        }

        $databasePath = GeneralUtility::getFileAbsFileName('uploads/tx_ip2geo/');

        if ($databaseName == '') {
            throw new Exception('Incorrect database name');
        }

        if (!is_dir($databasePath)) {
            throw new Exception('Database path does not exist');
        }

        if (!file_exists($databasePath . $databaseName .'_'. \SourceBroker\Ip2geo\Utility\GeneralUtility::getHash() . '.mmdb')) {
            throw new Exception('Database file does not exist');
        }

        $this->dbPath = $databasePath  . $databaseName .'_'. \SourceBroker\Ip2geo\Utility\GeneralUtility::getHash() . '.mmdb';
    }

    /**
     * Get instance of class. Returns exception if geoip extension is
     * not available.
     *
     * @param string $databaseName
     * @param null $ip
     * @return GeoIp
     * @throws Exception
     */
    public static function getInstance($databaseName = '', $ip = null)
    {
        if ($databaseName == '') {
            throw new Exception('No database selected');
        }

        return new self($databaseName, $ip);
    }

    /**
     * Get two-letter continent code
     *
     * @return false|null|string
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getContinentCode()
    {
        return $this->getCountry()->continent->code;
    }

    /**
     * Get two or three letter country code
     *
     * @return false|null|string
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getCountryCode()
    {
        return $this->getCountry()->country->isoCode;
    }

    /**
     * Get country name
     *
     * @return false|null|string
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getCountryName()
    {
        return $this->getCountry()->country->name;
    }

    /**
     * Get location record
     *
     * @return array|false
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getLocation()
    {
        // Map data
        return [
            'continentCode' => $this->getCity()->continent->code,
            'countryCode' => $this->getCity()->country->isoCode,
            'countryName' => $this->getCity()->country->name,
            'city' => $this->getCity()->city->name,
            'postalCode' => $this->getCity()->postal->code,
            'latitude' => $this->getCity()->location->latitude,
            'longitude' => $this->getCity()->location->longitude
        ];
    }

    /**
     * @return Country
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    protected function getCountry()
    {
        if ($this->country === null) {
            $this->country = (new Reader($this->dbPath))->country($this->ip);
        }

        return $this->country;
    }

    /**
     * @return City
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    protected function getCity()
    {
        if ($this->city === null) {
            $this->city = (new Reader($this->dbPath))->city($this->ip);
        }

        return $this->city;
    }
}
