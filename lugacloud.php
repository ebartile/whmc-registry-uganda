<?php
/**
 * WHMCS SDK Registry Uganda Api Registrar Module
 *
 * Registrar Modules allow you to create modules that allow for domain
 * registration, management, transfers, and other functionality within
 * WHMCS.
 *
 * Registrar Modules are stored in a unique directory within the
 * modules/registrars/ directory that matches the module's unique name.
 * This name should be all lowercase, containing only letters and numbers,
 * and always start with a letter.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For
 * example this file, the filename is "registrarmodule.php" and therefore all
 * function begin "lugacloud_".
 *
 * If your module or third party API does not support a given function, you
 * should not define the function within your module. WHMCS recommends that
 * all registrar modules implement Register, Transfer, Renew, GetNameservers,
 * SaveNameservers, GetContactDetails & SaveContactDetails.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/domain-registrars/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Module\Registrar\Lugacloud\ApiClient;
// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

/**
 * Define module related metadata
 *
 * Provide some module information including the display name and API Version to
 * determine the method of decoding the input values.
 *
 * @return array
 */
function lugacloud_MetaData()
{
    return array(
        'DisplayName' => 'Luga Cloud Registrar',
        'APIVersion' => '1.1',
    );
}

/**
 * Define registrar configuration options.
 *
 * The values you return here define what configuration options
 * we store for the module. These values are made available to
 * each module function.
 *
 * You can store an unlimited number of configuration settings.
 * The following field types are supported:
 *  * Text
 *  * Password
 *  * Yes/No Checkboxes
 *  * Dropdown Menus
 *  * Radio Buttons
 *  * Text Areas
 *
 * @return array
 */
function lugacloud_getConfigArray()
{
    return [
        // Friendly display name for the module
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Registrar Module for WHMCS',
        ],
        // a text field type allows for single line text input
        'APIID' => [
            'FriendlyName' => 'API ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '1024',
            'Description' => 'Enter in megabytes',
        ],
        // a password field type allows for masked text input
        'APIPassword' => [
            'FriendlyName' => 'API Password',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter secret value here',
        ],
        // the yesno field type displays a single checkbox option
        'TestMode' => [
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to enable',
        ],        
    ];
}

/**
 * Register a domain.
 *
 * Attempt to register a domain with the domain registrar.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain registration order
 * * When a pending domain registration order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_RegisterDomain($params)
{
    logModuleCall('Registrarmodule', 'register', $params, $params);

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'];
    $apiID = $params['APIID'];
    $apiPassword = $params['APIPassword'];
    $registrationPeriod = $params['regperiod'];
    $testMode = $params['TestMode'];

    if(strlen($tld) < 3) {
        $tld = ".$tld";
    }
    $d = "$sld$tld";

    // Build post data
    $postfields = <<<DATA
    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <request cmd="register">
        <auth id="$apiID" password="$apiPassword" />
        <domain name="$d" period="$registrationPeriod" />
    </request>
    DATA;    

    try {

        $api = new ApiClient();
        $api->call('Register', $postfields, $testMode);

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    /**
     * Nameservers.
     *
     * If purchased with web hosting, values will be taken from the
     * assigned web hosting server. Otherwise uses the values specified
     * during the order process.
     */
    $nameserver1 = $params['ns1'];
    $nameserver2 = $params['ns2'];
    $nameserver3 = $params['ns3'];
    $nameserver4 = $params['ns4'];

    // registrant information
    $firstName = $params["firstname"];
    $lastName = $params["lastname"];
    $fullName = $params["fullname"]; // First name and last name combined
    $companyName = $params["companyname"];
    $email = $params["email"];
    $address1 = $params["address1"];
    $address2 = $params["address2"];
    $city = $params["city"];
    $state = $params["state"]; // eg. TX
    $stateFullName = $params["fullstate"]; // eg. Texas
    $postcode = $params["postcode"]; // Postcode/Zip code
    $countryCode = $params["countrycode"]; // eg. GB
    $countryName = $params["countryname"]; // eg. United Kingdom
    $phoneNumber = $params["phonenumber"]; // Phone number as the user provided it
    $phoneCountryCode = $params["phonecc"]; // Country code determined based on country
    $phoneNumberFormatted = $params["fullphonenumber"]; // Format: +CC.xxxxxxxxxxxx

    /**
     * Admin contact information.
     *
     * Defaults to the same as the client information. Can be configured
     * to use the web hosts details if the `Use Clients Details` option
     * is disabled in Setup > General Settings > Domains.
     */
    $adminFirstName = $params["adminfirstname"];
    $adminLastName = $params["adminlastname"];
    $adminCompanyName = $params["admincompanyname"];
    $adminEmail = $params["adminemail"];
    $adminAddress1 = $params["adminaddress1"];
    $adminAddress2 = $params["adminaddress2"];
    $adminCity = $params["admincity"];
    $adminState = $params["adminstate"]; // eg. TX
    $adminStateFull = $params["adminfullstate"]; // eg. Texas
    $adminPostcode = $params["adminpostcode"]; // Postcode/Zip code
    $adminCountry = $params["admincountry"]; // eg. GB
    $adminPhoneNumber = $params["adminphonenumber"]; // Phone number as the user provided it
    $adminPhoneNumberFormatted = $params["adminfullphonenumber"]; // Format: +CC.xxxxxxxxxxxx

    // Build post data
    $postfields = <<<DATA
    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <request cmd="register_specific">
        <auth id="$apiID" password="$apiPassword" />
        <domain name="$d" />
        <contacts>
            <registrant 
                firstname="$firstName" 
                lastname="$lastName" 
                email="$email" 
                organization="$companyName" 
                country="$countryName" 
                city="$city" 
                street_address="$address1" 
                phone="$phoneNumberFormatted" 
                fax="" />
            <admin 
                firstname="$adminFirstName" 
                lastname="$adminLastName" 
                email="$adminEmail" 
                organization="$adminCompanyName" 
                country="$adminCountry" 
                city="$adminCity" 
                street_address="$adminAddress1" 
                phone="$adminPhoneNumberFormatted" 
                fax="" />
        </contacts>
        <nameservers>
            <ns1 name="$nameserver1" ip="" />
            <ns2 name="$nameserver2" ip="" />
            <ns3 name="$nameserver3" ip="" />
            <ns4 name="$nameserver4" ip="" />
        </nameservers>        
    </request>
    DATA;    
    
    try {
        $api = new ApiClient();
        $api->call('Register Specific', $postfields, $testMode);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Initiate domain transfer.
 *
 * Attempt to create a domain transfer request for a given domain.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain transfer order
 * * When a pending domain transfer order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_TransferDomain($params)
{
    logModuleCall('Registrarmodule', 'Transfer Domain', $params, $params);

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'];
    $apiID = $params['APIID'];
    $apiPassword = $params['APIPassword'];
    $testMode = $params['TestMode'];

    if(strlen($tld) < 3) {
        $tld = ".$tld";
    }
    $d = "$sld$tld";

    // Build post data
    $postfields = <<<DATA
    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <request cmd="request_transfer">
        <auth id="$apiID" password="$apiPassword" />
        <domain name="$d" />
    </request>
    DATA;    

    try {

        $api = new ApiClient();
        $api->call('Request Transfer', $postfields, $testMode);

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    // Build post data
    $postfields = <<<DATA
    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <request cmd="confirm_transfer">
        <auth id="$apiID" password="$apiPassword" />
        <domain name="$d" transfer_id="11" />
    </request>
    DATA;    

    try {

        $api = new ApiClient();
        $api->call('Confirm Transfer', $postfields, $testMode);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

}

/**
 * Renew a domain.
 *
 * Attempt to renew/extend a domain for a given number of years.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain renewal order
 * * When a pending domain renewal order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_RenewDomain($params)
{
    logModuleCall('Registrarmodule', 'Renew', $params, $params);

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'];
    $registrationPeriod = $params['regperiod'];
    $apiID = $params['APIID'];
    $apiPassword = $params['APIPassword'];
    $testMode = $params['TestMode'];

    if(strlen($tld) < 3) {
        $tld = ".$tld";
    }
    $d = "$sld$tld";

    // Build post data
    $postfields = <<<DATA
    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <request cmd="request_transfer">
        <domain name="$d" period="$registrationPeriod" />
    </request>
    DATA;    

    try {
        $api = new ApiClient();
        $api->call('Renew', $postfields, $testMode);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Fetch current nameservers.
 *
 * This function should return an array of nameservers for a given domain.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_GetNameservers($params)
{
    logModuleCall('Registrarmodule', 'GetNameservers', $params, $params);

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'];
    $apiID = $params['APIID'];
    $apiPassword = $params['APIPassword'];
    $testMode = $params['TestMode'];

    if(strlen($tld) < 3) {
        $tld = ".$tld";
    }
    $d = "$sld$tld";

    // Build post data
    $postfields = <<<DATA
    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <request cmd="whois">
        <domain name="$d" />
    </request>
    DATA;    

    try {
        $api = new ApiClient();
        $api->call('GetNameservers', $postfields, $testMode);

        return array(
            'success' => true,
        );

        // return array(
        //     'success' => true,
        //     'ns1' => $api->getFromResponse('nameservers')[],
        //     'ns2' => $api->getFromResponse('nameserver2'),
        //     'ns3' => $api->getFromResponse('nameserver3'),
        //     'ns4' => $api->getFromResponse('nameserver4'),
        //     'ns5' => $api->getFromResponse('nameserver5'),
        // );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Save nameserver changes.
 *
 * This function should submit a change of nameservers request to the
 * domain registrar.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_SaveNameservers($params)
{
    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // submitted nameserver values
    $nameserver1 = $params['ns1'];
    $nameserver2 = $params['ns2'];
    $nameserver3 = $params['ns3'];
    $nameserver4 = $params['ns4'];
    $nameserver5 = $params['ns5'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'nameserver1' => $nameserver1,
        'nameserver2' => $nameserver2,
        'nameserver3' => $nameserver3,
        'nameserver4' => $nameserver4,
        'nameserver5' => $nameserver5,
    );

    try {
        $api = new ApiClient();
        $api->call('SetNameservers', $postfields, $testMode);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Get the current WHOIS Contact Information.
 *
 * Should return a multi-level array of the contacts and name/address
 * fields that be modified.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_GetContactDetails($params)
{
    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];


    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
    );

    try {
        $api = new ApiClient();
        $api->call('GetWhoisInformation', $postfields, $testMode);

        return array(
            'Registrant' => array(
                'First Name' => $api->getFromResponse('registrant.firstname'),
                'Last Name' => $api->getFromResponse('registrant.lastname'),
                'Company Name' => $api->getFromResponse('registrant.company'),
                'Email Address' => $api->getFromResponse('registrant.email'),
                'Address 1' => $api->getFromResponse('registrant.address1'),
                'Address 2' => $api->getFromResponse('registrant.address2'),
                'City' => $api->getFromResponse('registrant.city'),
                'State' => $api->getFromResponse('registrant.state'),
                'Postcode' => $api->getFromResponse('registrant.postcode'),
                'Country' => $api->getFromResponse('registrant.country'),
                'Phone Number' => $api->getFromResponse('registrant.phone'),
                'Fax Number' => $api->getFromResponse('registrant.fax'),
            ),
            'Technical' => array(
                'First Name' => $api->getFromResponse('tech.firstname'),
                'Last Name' => $api->getFromResponse('tech.lastname'),
                'Company Name' => $api->getFromResponse('tech.company'),
                'Email Address' => $api->getFromResponse('tech.email'),
                'Address 1' => $api->getFromResponse('tech.address1'),
                'Address 2' => $api->getFromResponse('tech.address2'),
                'City' => $api->getFromResponse('tech.city'),
                'State' => $api->getFromResponse('tech.state'),
                'Postcode' => $api->getFromResponse('tech.postcode'),
                'Country' => $api->getFromResponse('tech.country'),
                'Phone Number' => $api->getFromResponse('tech.phone'),
                'Fax Number' => $api->getFromResponse('tech.fax'),
            ),
            'Billing' => array(
                'First Name' => $api->getFromResponse('billing.firstname'),
                'Last Name' => $api->getFromResponse('billing.lastname'),
                'Company Name' => $api->getFromResponse('billing.company'),
                'Email Address' => $api->getFromResponse('billing.email'),
                'Address 1' => $api->getFromResponse('billing.address1'),
                'Address 2' => $api->getFromResponse('billing.address2'),
                'City' => $api->getFromResponse('billing.city'),
                'State' => $api->getFromResponse('billing.state'),
                'Postcode' => $api->getFromResponse('billing.postcode'),
                'Country' => $api->getFromResponse('billing.country'),
                'Phone Number' => $api->getFromResponse('billing.phone'),
                'Fax Number' => $api->getFromResponse('billing.fax'),
            ),
            'Admin' => array(
                'First Name' => $api->getFromResponse('admin.firstname'),
                'Last Name' => $api->getFromResponse('admin.lastname'),
                'Company Name' => $api->getFromResponse('admin.company'),
                'Email Address' => $api->getFromResponse('admin.email'),
                'Address 1' => $api->getFromResponse('admin.address1'),
                'Address 2' => $api->getFromResponse('admin.address2'),
                'City' => $api->getFromResponse('admin.city'),
                'State' => $api->getFromResponse('admin.state'),
                'Postcode' => $api->getFromResponse('admin.postcode'),
                'Country' => $api->getFromResponse('admin.country'),
                'Phone Number' => $api->getFromResponse('admin.phone'),
                'Fax Number' => $api->getFromResponse('admin.fax'),
            ),
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Update the WHOIS Contact Information for a given domain.
 *
 * Called when a change of WHOIS Information is requested within WHMCS.
 * Receives an array matching the format provided via the `GetContactDetails`
 * method with the values from the users input.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_SaveContactDetails($params)
{
    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // whois information
    $contactDetails = $params['contactdetails'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'contacts' => array(
            'registrant' => array(
                'firstname' => $contactDetails['Registrant']['First Name'],
                'lastname' => $contactDetails['Registrant']['Last Name'],
                'company' => $contactDetails['Registrant']['Company Name'],
                'email' => $contactDetails['Registrant']['Email Address'],
                // etc...
            ),
            'tech' => array(
                'firstname' => $contactDetails['Technical']['First Name'],
                'lastname' => $contactDetails['Technical']['Last Name'],
                'company' => $contactDetails['Technical']['Company Name'],
                'email' => $contactDetails['Technical']['Email Address'],
                // etc...
            ),
            'billing' => array(
                'firstname' => $contactDetails['Billing']['First Name'],
                'lastname' => $contactDetails['Billing']['Last Name'],
                'company' => $contactDetails['Billing']['Company Name'],
                'email' => $contactDetails['Billing']['Email Address'],
                // etc...
            ),
            'admin' => array(
                'firstname' => $contactDetails['Admin']['First Name'],
                'lastname' => $contactDetails['Admin']['Last Name'],
                'company' => $contactDetails['Admin']['Company Name'],
                'email' => $contactDetails['Admin']['Email Address'],
                // etc...
            ),
        ),
    );

    try {
        $api = new ApiClient();
        $api->call('UpdateWhoisInformation', $postfields, $testMode);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Check Domain Availability.
 *
 * Determine if a domain or group of domains are available for
 * registration or transfer.
 *
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 *
 * @throws Exception Upon domain availability check failure.
 *
 * @return \WHMCS\Domains\DomainLookup\ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 */
function lugacloud_CheckAvailability($params)
{
    logModuleCall('Registrarmodule', 'Avialability', $params, $params);

    // registration parameters
    $sld = $params['sld'];
    $tlds = $params['tlds'];
    $testMode = $params['TestMode'];
    $query = "";
    foreach ($tlds as $tld) {
        if(substr($tld, 0, 1) != '.') {
            $tld = ".$tld";
        }
        $query = $query."<domain name='".$sld.$tld."'></domain>";
    }


    // Build post data
    $postfields = <<<DATA
    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <request cmd="check">
        <domains>
            $query
        </domains>
    </request>
    DATA;    

    try {
        $api = new ApiClient();
        $api->call('Avialability', $postfields, $testMode);

        $results = new ResultsList();
        $domains = $api->getFromResponse('domains');

        foreach ($domains['domain'] as $domain) {

            if(count($domain) > 1) {
                $tld = strstr($domain['name'], '.');

                // Instantiate a new domain search result object
                $searchResult = new SearchResult($sld, $tld);
            
                // Determine the appropriate status to return
                if ((boolval($domain['avail']))) {
                    $status = SearchResult::STATUS_NOT_REGISTERED;
                } else {
                    $status = SearchResult::STATUS_REGISTERED;
                }
    
                $searchResult->setStatus($status);
    
                // Append to the search results list
                $results->append($searchResult);    
            } else {
                foreach ($domain as $d) {
                    $tld = strstr($d['name'], '.');

                    // Instantiate a new domain search result object
                    $searchResult = new SearchResult($sld, $tld);
                
                    // Determine the appropriate status to return
                    if ((boolval($d['avail']))) {
                        $status = SearchResult::STATUS_NOT_REGISTERED;
                    } else {
                        $status = SearchResult::STATUS_REGISTERED;
                    }
        
                    $searchResult->setStatus($status);
        
                    // Append to the search results list
                    $results->append($searchResult);    
                }
            }
            
        }

        return $results;

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Get Domain Suggestions.
 *
 * Provide domain suggestions based on the domain lookup term provided.
 *
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 *
 * @throws Exception Upon domain suggestions check failure.
 *
 * @return \WHMCS\Domains\DomainLookup\ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 */
function lugacloud_GetDomainSuggestions($params)
{
    $results = new ResultsList();
    return $results;
}

/**
 * Get registrar lock status.
 *
 * Also known as Domain Lock or Transfer Lock status.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return string|array Lock status or error message
 */
function lugacloud_GetRegistrarLock($params)
{
    return 'locked';
}

/**
 * Set registrar lock status.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_SaveRegistrarLock($params)
{
    return array(
        'success' => 'success',
    );
}

/**
 * Get DNS Records for DNS Host Record Management.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array DNS Host Records
 */
function lugacloud_GetDNS($params)
{
    return array(
        'success' => 'success',
    );
}

/**
 * Update DNS Host Records.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_SaveDNS($params)
{
    return array(
        'success' => 'success',
    );
}

/**
 * Enable/Disable ID Protection.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_IDProtectToggle($params)
{
    return array(
        'success' => 'success',
    );
}

/**
 * Request EEP Code.
 *
 * Supports both displaying the EPP Code directly to a user or indicating
 * that the EPP Code will be emailed to the registrant.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 *
 */
function lugacloud_GetEPPCode($params)
{
    return array(
        'success' => 'success',
    );
}

/**
 * Release a Domain.
 *
 * Used to initiate a transfer out such as an IPSTAG change for .UK
 * domain names.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_ReleaseDomain($params)
{
    return array(
        'success' => 'success',
    );
}

/**
 * Delete Domain.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_RequestDelete($params)
{
    return array(
        'success' => 'success',
    );
}

/**
 * Register a Nameserver.
 *
 * Adds a child nameserver for the given domain name.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_RegisterNameserver($params)
{
    return array(
        'success' => 'success',
    );
}

/**
 * Modify a Nameserver.
 *
 * Modifies the IP of a child nameserver.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_ModifyNameserver($params)
{
    return array(
        'success' => 'success',
    );
}

/**
 * Delete a Nameserver.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_DeleteNameserver($params)
{
    return array(
        'success' => 'success',
    );
}

/**
 * Sync Domain Status & Expiration Date.
 *
 * Domain syncing is intended to ensure domain status and expiry date
 * changes made directly at the domain registrar are synced to WHMCS.
 * It is called periodically for a domain.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_Sync($params)
{
    // No status change, return empty array
    return array();
}

/**
 * Incoming Domain Transfer Sync.
 *
 * Check status of incoming domain transfers and notify end-user upon
 * completion. This function is called daily for incoming domains.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_TransferSync($params)
{
    // No status change, return empty array
    return array();
}

/**
 * Client Area Custom Button Array.
 *
 * Allows you to define additional actions your module supports.
 * In this example, we register a Push Domain action which triggers
 * the `lugacloud_push` function when invoked.
 *
 * @return array
 */
function lugacloud_ClientAreaCustomButtonArray()
{
    return array(
        'Push Domain' => 'push',
    );
}

/**
 * Client Area Allowed Functions.
 *
 * Only the functions defined within this function or the Client Area
 * Custom Button Array can be invoked by client level users.
 *
 * @return array
 */
function lugacloud_ClientAreaAllowedFunctions()
{
    return array(
        'Push Domain' => 'push',
    );
}

/**
 * Example Custom Module Function: Push
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function lugacloud_push($params)
{
    return 'Not implemented';
}

/**
 * Client Area Output.
 *
 * This function renders output to the domain details interface within
 * the client area. The return should be the HTML to be output.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return string HTML Output
 */
function lugacloud_ClientArea($params)
{
    $output = '
        <div class="alert alert-info">
            Your custom HTML output goes here...
        </div>
    ';

    return $output;
}

