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

    $holder = [
        'company' => $params['companyname'],
        'firstname' => $params['firstname'],
        'lastname' => $params['lastname'],
        'street' => $params['address1'],
        'zipcode' => $params['postcode'],
        'city' => $params['city'],
        'email' => $params['email']
    ];

    if (!$holderId = $client->findHolderId($holder)) {
        $holder['phone_number'] = $params['phonenumber'];
        $holder['country_code'] = $params['countrycode'];

        $holderId = $client->createHolder($holder);
    }

    $rc = ['error' => "Could not create contact '{$params['firstname']} {$params['lastname']}'"];

    if ($holderId) {
        $rc = ['error' => "Could not register domain '{$params['domainname']}'"];

        $nameservers = array_filter([$params['ns1'], $params['ns2'], $params['ns3']]);

        $order = $client->order(
            $params['domainname'],
            $holderId,
            $params['regperiod'],
            $nameservers
        );

        if ($order) {
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

    $holder = [
        'company' => $params['companyname'],
        'firstname' => $params['firstname'],
        'lastname' => $params['lastname'],
        'street' => $params['address1'],
        'zipcode' => $params['postcode'],
        'city' => $params['city'],
        'email' => $params['email']
    ];

    if (!$holderId = $client->findHolderId($holder)) {
        $holder['phone_number'] = $params['phonenumber'];
        $holder['country_code'] = $params['countrycode'];

        $holderId = $client->createHolder($holder);
    }

    $rc = ['error' => "Could not create contact '{$params['firstname']} {$params['lastname']}'"];

    if ($holderId) {
        $rc = ['error' => "Could not transfer domain '{$params['domainname']}'"];

        $nameservers = array_filter([$params['ns1'], $params['ns2'], $params['ns3']]);

        $order = $client->order(
            $params['domainname'],
            $holderId,
            $params['regperiod'],
            $nameservers,
            $params['transfersecret']
        );

        if ($order) {
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
 * returns the full name. Also get a usable phone number.
 *
 * This is a VERY hacky way of getting this information. Don't try this
 * at home. ;-)
 *
 * @param Client $client
 * @param $holderToAlter
 * @return array|null
 */
function getUsableContact(Client &$client, $holderToAlter)
{
    if ($holders = $client->getHolders()) {
        $name = $holderToAlter['name'];

        foreach ($holders as $holder) {
            if (strpos($name, $holder['firstname']) !== false) {
                $holderToAlter['firstname'] = $holder['firstname'];

                $name = trim(str_ireplace($holder['firstname'], '', $name));
            }

            // Determine which string to use to find the last name in
            if (strlen($name) < strlen($holder['lastname'])) {
                $haystack = $holder['lastname'];
                $needle = $name;
            } else {
                $haystack = $name;
                $needle = $holder['lastname'];
            }

            if (strpos($haystack, $needle) !== false) {
                $holderToAlter['lastname'] = $holder['lastname'];
            }

            // Get the phone number without its country code
            if (!empty($holderToAlter['phone'])) {
                $phoneNumber = explode('.', $holderToAlter['phone'], 2);

                $holderToAlter['phone'] = $phoneNumber[1] ?: $holderToAlter['phone'];
            }
        }
    }

    return $holderToAlter;
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
        $registrant = getUsableContact($client, $domain['registrant']);
        $tech = getUsableContact($client, $domain['tech']);
        $admin = getUsableContact($client, $domain['admin']);

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
                'Phone Number' => $registrant['phone']
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
                'Phone Number' => $tech['phone']
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
                'Phone Number' => $admin['phone']
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
            $deleteRecords[$record['id']] = $record['id'];
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
            unset($deleteRecords[$record['id']]);
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
 * Get the EPP code for the domain.
 *
 * @param $params
 * @return array
 */
function neostrada_GetEPPCode($params)
{
    $client = new Client($params['key']);

    $domain = $client->getDomain($params['domainname']);

    if ($domain && isset($domain['auth_code'])) {
        return ['eppcode' => $domain['auth_code']];
    }

    return ['error' => 'Could not fetch EPP ode'];
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
 * Get details about the domain from the WHMCS database.
 *
 * @param $domainId
 * @return array
 */
function getDomainDetails($domainId)
{
    $result = localAPI('GetClientsDomains', ['domainid' => $domainId]);

    $rc = [];

    if (isset($result['domains']['domain'][0]) && $result['domains']['domain'][0]['id'] == $domainId) {
        $rc = $result['domains']['domain'][0];
    }

    return $rc;
}

/**
 * Sync existing domain registrations.
 *
 * @param $params
 * @return array
 * @throws Exception
 */
function neostrada_Sync($params)
{
    $client = new Client($params['key']);

    if (!$domainDetails = getDomainDetails($params['domainid'])) {
        return ['error' => 'Could not get domain details for '.$params['domain']];
    }

    $autoRenew = $domainDetails['donotrenew'] == 0;

    $rc = ['error' => "Could not sync domain {$params['domain']}"];

    $domain = $client->getDomain($params['domain']);

    if ($domain && $expiresAt = DateTime::createFromFormat(DateTime::ATOM, $domain['paid_until'])) {
        $active = $domain['status'] == 'active';

        // Delete or reactivate the domain based on the auto renew status of the domain
        if (!$autoRenew && $active) {
            if (!$client->deleteDomain($params['domain'])) {
                return ['error' => 'Could not deactivate auto renewal for '.$params['domain']];
            }

            $active = false;
        } elseif ($autoRenew && !$active) {
            if (!$client->reactivateDomain($params['domain'])) {
                return ['error' => 'Could not activate auto renewal for '.$params['domain']];
            }

            $active = true;
        }

        $expired = false;

        if ($expiresAt < new DateTime()) {
            // Force $active to be false when the expiry has passed
            $active = false;
            $expired = true;
        }

        $rc = [
            'expirydate' => $expiresAt->format('Y-m-d'),
            'active' => $active,
            'expired' => $expired,
            'transferredAway' => false
        ];
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

    $domain = $client->getDomain($params['domain']);

    if ($domain && $expiresAt = DateTime::createFromFormat(DateTime::ATOM, $domain['paid_until'])) {
        $rc = [
            'completed' => true,
            'expirydate' => $expiresAt->format('Y-m-d')
        ];
    }

    return $rc;
}

/**
 * Renew a domain.
 *
 * Domains are always renewed, and it's currently not possible to manually renew
 * a domain. This function always results in a success to prevent WHMCS from
 * creating tasks for the admin to check domain renewals.
 *
 * @return array
 */
function neostrada_RenewDomain($params)
{
    return ['success' => true];
}
