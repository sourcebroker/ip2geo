TYPO3 Extension ip2geo
======================

    .. image:: https://poser.pugx.org/sourcebroker/ip2geo/license
       :target: https://packagist.org/packages/sourcebroker/ip2geo

.. contents:: :local:

What does it do?
----------------

This extension provides scheduler job to download maxmind database (free or commercial) and some small class with methods
to get interesting content like country, continent etc.

How to install?
---------------

1) Install using composer:

   ::

    composer require sourcebroker/ip2geo

2) Go to Scheduler module and add new job "Download geolocation database"

3) While adding scheduler job you need two parameters:

   a) ``Database name``. This can an be any string. Example: "freeCountry", "commercialCountry". This name will be used
      later in php code like ``GeoIp::getInstance('freeCountry')`` .

   b) ``Download URL``. Maxmind database download link.

      * For country lite database the url is: https://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz

      * For city lite database the url is: https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz

      * If the links above does not work here is the page at maxmind with list databases: https://dev.maxmind.com/geoip/geoip2/geolite2/

4) Run the scheduler and check folder ``/uploads/tx_ip2geo/``. The database should be downloaded there.

How to use?
---------------

In your code get the data with following call:

1) For country database:

   ::

     $countryCode = GeoIp::getInstance('freeCountry')->getCountryCode(); // assuming you named database with "freeCountry" in scheduler task

   For IP 83.97.23.149 you will get "DE" as response:

   ::


2) For city database:

   ::

     $locationData = GeoIp::getInstance('freeCity')->getLocation(); // assuming you named database with "freeCity" in scheduler task

   For IP 83.97.23.149 you will get following data as response:

   ::

     Array
     (
        [continentCode] => EU
        [countryCode] => DE
        [countryName] => Germany
        [city] => Berlin
        [postalCode] => 10178
        [latitude] => 52.5196
        [longitude] => 13.4069
     )

Additional options
------------------

You can set some options for this extension in ``$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ip2geo']``. You can use
``typo3conf/LocalConfiguration`` file to store this values.

1) ``defaultLocalIP``
   For local development you IP is usually 127.0.0.1 and this IP of course does not exist in Maxmind database. For this
   situation Maxminf API will return "The address 127.0.0.1 is not in the database.". You can fix it by setting default
   IP if the IP is detected as 127.0.0.1. Example configuration:

    ::

          'EXTCONF' => [
            'ip2geo' => [
              'defaultLocalIP' => '83.97.23.149',
            ],
          ],

2) ``fakeIpHeaderName``
   This is a name of header which you can use to overwrite the value of IP. This value must be unique so nobody except you
   can overwrite IP. TIP: a nice chrome extension for setting headers is "ModHeader". Example:

    ::

          'EXTCONF' => [
            'ip2geo' => [
              'fakeIpHeaderName' => 'myFakeIpHeader1991718263162831',
            ],
          ],


``fakeIpHeaderName`` has precedence over ``defaultLocalIP`` when both are set.


Changelog
---------

See https://github.com/sourcebroker/ip2geo/blob/master/CHANGELOG.rst
