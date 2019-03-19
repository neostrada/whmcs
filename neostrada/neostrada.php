<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once 'vendor/autoload.php';
require_once 'Http.php';
require_once 'Client.php';

use Neostrada\Client;

/**
 * Module metadata.
 *
 * @return array
 */
function neostrada_MetaData()
{
    return [
        'DisplayName' => 'Neostrada',
        'APIVersion' => '2.0'
    ];
}

/**
 * Module configuration.
 *
 * @return array
 */
function neostrada_getConfigArray()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Neostrada'
        ],
        'key' => [
            'FriendlyName' => 'API key',
            'Type' => 'password',
            'Size' => '25',
            'Description' => 'Enter your API key here. You can retrieve it through My Account on the Neostrada website.',
        ]
    ];
}

/**
 * Register a domain.
 *
 * @param $params
 * @return array
 */
function neostrada_RegisterDomain($params)
{
    $client = new Client($params['key']);

    $holderId = $client->createHolder([
        'company' => $params['companyname'],
        'firstname' => $params['firstname'],
        'lastname' => $params['lastname'],
        'phone_number' => $params['phonenumber'],
        'street' => $params['address1'],
        'zipcode' => $params['postcode'],
        'city' => $params['city'],
        'country_code' => $params['countrycode'],
        'email' => $params['email']
    ]);

    $rc = ['error' => 'Could not create contact'];

    if ($holderId) {
        $rc = ['error' => 'Could not register domain'];

        if ($client->order($params['domainname'], $holderId, $params['regperiod'])) {
            $rc = ['success' => true];
        }
    }

    return $rc;
}

/**
 * Transfer a domain.
 *
 * @param $params
 * @return array
 */
function neostrada_TransferDomain($params)
{
    $client = new Client($params['key']);

    $holderId = $client->createHolder([
        'company' => $params['companyname'],
        'firstname' => $params['firstname'],
        'lastname' => $params['lastname'],
        'phone_number' => $params['phonenumber'],
        'street' => $params['address1'],
        'zipcode' => $params['postcode'],
        'city' => $params['city'],
        'country_code' => $params['countrycode'],
        'email' => $params['email']
    ]);

    $rc = ['error' => 'Could not create contact'];

    if ($holderId) {
        $rc = ['error' => 'Could not register domain'];

        if ($client->order($params['domainname'], $holderId, $params['regperiod'], $params['transfersecret'])) {
            $rc = ['success' => true];
        }
    }

    return $rc;
}

/**
 * Get the nameservers of a domain.
 *
 * @param $params
 * @return array
 */
function neostrada_GetNameservers($params)
{
    $client = new Client($params['key']);

    $rc = ['error' => 'Could not get nameservers'];

    $nameservers = $client->getNamesevers($params['domainname']);

    if (!is_null($nameservers)) {
        // The call succeeded, but there are no nameservers to show
        if (empty($nameservers)) {
            $rc = ['success' => true];
        } else {
            $rc = [];

            foreach ($nameservers as $key => $nameserver) {
                $key++;

                $rc["ns{$key}"] = $nameserver['content'];
            }
        }
    }

    return $rc;
}

/**
 * Save nameservers.
 *
 * @param $params
 * @return array
 */
function neostrada_SaveNameservers($params)
{
    $client = new Client($params['key']);

    $domain = $params['domainname'];

    $nameservers = [$params['ns1'], $params['ns2'], $params['ns3']];
    $nameservers = array_filter($nameservers);

    $rc = ['error' => 'Could not save nameservers'];

    if (!empty($nameservers)) {
        $client->deleteCurrentNameservers($domain);

        if ($client->addNameservers($domain, $nameservers)) {
            $rc = ['success' => true];
        }
    }

    return $rc;
}

/**
 * Add the first and last name to the holder, because the API only
 * returns the full name.
 *
 * @param Client $client
 * @param $holderToAlter
 * @return array|null
 */
function addFirstAndLastName(Client $client, $holderToAlter)
{
    $names = [
        'firstname' => '',
        'lastname' => ''
    ];

    if ($holders = $client->getHolders()) {
        foreach ($holders as $holder) {
            $name = $holder['firstname'];

            if (!empty($holder['center'])) {
                $name .= " {$holder['center']}";
            }

            $name .= " {$holder['lastname']}";

            if ($name == $holderToAlter['name']) {
                $names = [
                    'firstname' => $holder['firstname'],
                    'lastname' => $holder['lastname']
                ];
                break;
            }
        }
    }

    $rc = null;

    if ($names) {
        $rc = array_merge($holderToAlter, $names);
    }

    return $rc;
}

/**
 * Get a domain's WHOIS details.
 *
 * @param $params
 * @return array
 */
function neostrada_GetContactDetails($params)
{
    $client = new Client($params['key']);

    $rc = ['error' => 'Could not get contact details'];

    if ($domain = $client->getDomain($params['domainname'])) {
        $registrant = addFirstAndLastName($client, $domain['registrant']);
        $tech = addFirstAndLastName($client, $domain['tech']);
        $admin = addFirstAndLastName($client, $domain['admin']);

        $rc = [
            'Registrant' => [
                'First Name' => $registrant['firstname'],
                'Last Name' => $registrant['lastname'],
                'Company Name' => $registrant['company'],
                'Email Address' => $registrant['email'],
                'Address 1' => $registrant['street'],
                'City' => $registrant['city'],
                'Postcode' => $registrant['zipcode'],
                'Country' => $registrant['country_code'],
                'Phone Number' => $registrant['']
            ],
            'Technical' => [
                'First Name' => $tech['firstname'],
                'Last Name' => $tech['lastname'],
                'Company Name' => $tech['company'],
                'Email Address' => $tech['email'],
                'Address 1' => $tech['street'],
                'City' => $tech['city'],
                'Postcode' => $tech['zipcode'],
                'Country' => $tech['country_code'],
                'Phone Number' => $tech['']
            ],
            'Admin' => [
                'First Name' => $admin['firstname'],
                'Last Name' => $admin['lastname'],
                'Company Name' => $admin['company'],
                'Email Address' => $admin['email'],
                'Address 1' => $admin['street'],
                'City' => $admin['city'],
                'Postcode' => $admin['zipcode'],
                'Country' => $admin['country_code'],
                'Phone Number' => $admin['']
            ]
        ];
    }

    return $rc;
}

/**
 * Try to find the holder ID based on the details from WHMCS.
 * Otherwise create the holder and return its new ID.
 *
 * @param Client $client
 * @param $whmcsHolder
 * @return int|null
 */
function getOrCreateHolder(Client $client, $whmcsHolder)
{
    $holder = [
        'company' => $whmcsHolder['Company Name'],
        'firstname' => $whmcsHolder['First Name'],
        'lastname' => $whmcsHolder['Last Name'],
        'street' => $whmcsHolder['Address 1'],
        'zipcode' => $whmcsHolder['Postcode'],
        'city' => $whmcsHolder['City'],
        'email' => $whmcsHolder['Email Address']
    ];

    $rc = $client->findHolderId($holder);

    // Looks like the holder doesn't exist yet
    if (!$rc) {
        $phoneNumber = explode('.', $whmcsHolder['Phone Number'], 2);

        $holder = array_merge($holder, [
            'phone_number' => isset($phoneNumber[1]) ? $phoneNumber[1] : '',
            'country_code' => $whmcsHolder['Country']
        ]);

        if ($holderId = $client->createHolder($holder)) {
            $rc = $holderId;
        }
    }

    return $rc;
}

/**
 * Update a domain's WHOIS details.
 *
 * @param $params
 * @return array
 */
function neostrada_SaveContactDetails($params)
{
    $client = new Client($params['key']);

    $registrantId = getOrCreateHolder($client, $params['contactdetails']['Registrant']);
    $techId = getOrCreateHolder($client, $params['contactdetails']['Technical']);
    $adminId = getOrCreateHolder($client, $params['contactdetails']['Admin']);

    $updated = false;

    // Update the domain holders when we have all three holder IDs
    if ($registrantId && $techId && $adminId) {
        $updated = $client->updateDomainHolders($params['domainname'], [
            'registrar' => $registrantId,
            'tech' => $techId,
            'admin' => $adminId
        ]);
    }

    $rc = ['error' => 'Could not change contact for domain'];

    if ($updated) {
        $rc = ['success' => true];
    }

    return $rc;
}

/**
 * Get DNS records.
 *
 * @param $params
 * @return array
 */
function neostrada_GetDNS($params)
{
    $client = new Client($params['key']);

    $rc = ['error' => 'Could not fetch records'];

    $records = $client->getDnsRecords($params['domainname']);

    if (!is_null($records)) {
        $rc = [];

        if (!empty($records)) {
            foreach ($records as $record) {
                $rc[] = [
                    'recid' => $record['id'],
                    'hostname' => $record['name'],
                    'type' => $record['type'],
                    'address' => $record['content'],
                    'priority' => $record['prio']
                ];
            }
        }
    }

    return $rc;
}

/**
 * Update DNS records.
 *
 * @param $params
 * @return array
 */
function neostrada_SaveDNS($params)
{
    $client = new Client($params['key']);

    $domain = $params['domainname'];

    $deleteRecords = [];
    $updateRecords = [];
    $addRecords = [];

    if ($records = $client->getDnsRecords($domain)) {
        foreach ($records as $record) {
            $deleteRecords[] = $record['id'];
        }
    }

    foreach ($params['dnsrecords'] as $record) {
        // Update an existing record
        if (!empty($record['recid']) && !empty($record['hostname']) && !empty($record['address'])) {
            $updateRecords[] = [
                'record_id' => $record['recid'],
                'content' => $record['address'],
                'prio' => $record['priority'] != 'N/A' ? $record['priority'] : ''
            ];

            // This record can be updated, so remove it from the array of records to delete
            unset($deleteRecords[array_search($record['recid'], $deleteRecords)]);
        }

        // Add a new record
        if (empty($record['recid'])) {
            $addRecords[] = [
                'name' => $record['hostname'],
                'type' => $record['type'],
                'content' => $record['address'],
                'prio' => $record['priority']
            ];
        }
    }

    if (!empty($deleteRecords)) {
        $client->deleteDnsRecords($domain, $deleteRecords);
    }

    if (!empty($updateRecords)) {
        $client->updateDnsRecords($domain, $updateRecords);
    }

    if (!empty($addRecords)) {
        $client->addDnsRecords($domain, $addRecords);
    }

    return ['success' => true];
}

/**
 * Delete a domain.
 *
 * @param $params
 * @return array
 */
function neostrada_RequestDelete($params)
{
    $client = new Client($params['key']);

    $rc = ['error' => 'Could not delete domain'];

    if ($client->deleteDomain($params['domainname'])) {
        $rc = ['success' => true];
    }

    return $rc;
}

/**
 * Sync existing domain registrations.
 *
 * @param $params
 * @return array
 */
function neostrada_Sync($params)
{
    $client = new Client($params['key']);

    $domain = $params['domain'];

    $rc = ['error' => "Could not sync domain {$domain}"];

    if ($domain = $client->getDomain($domain)) {
        $expiresAt = DateTime::createFromFormat(DATE_ATOM, $domain['paid_until']);
        $now = new DateTime();

        $rc = [
            'expirydate' => $expiresAt->format('Y-m-d'),
            'active' => $domain['status'] == 'active',
            'expired' => false,
            'transferredAway' => false
        ];

        if ($expiresAt < $now) {
            $rc['expired'] = true;
        }
    }

    return $rc;
}

/**
 * Sync incoming domain transfers.
 *
 * TODO: check if the domain is transferred when the API supports this. Until then, always set completed = true
 *
 * @param $params
 * @return array
 */
function neostrada_TransferSync($params)
{
    $client = new Client($params['key']);

    $rc = [];

    if ($domain = $client->getDomain($params['domain'])) {
        $expiresAt = DateTime::createFromFormat(DATE_ATOM, $domain['paid_until']);

        $rc = [
            'completed' => true,
            'expirydate' => $expiresAt->format('Y-m-d')
        ];
    }

    return $rc;
}

/**
 * Client Area Custom Button Array.
 *
 * Allows you to define additional actions your module supports.
 * In this example, we register a Push Domain action which triggers
 * the `neostrada_push` function when invoked.
 *
 * @return array
 */
function neostrada_ClientAreaCustomButtonArray()
{
    return [];
}

/**
 * Client Area Allowed Functions.
 *
 * Only the functions defined within this function or the Client Area
 * Custom Button Array can be invoked by client level users.
 *
 * @return array
 */
function neostrada_ClientAreaAllowedFunctions()
{
    return [];
}
