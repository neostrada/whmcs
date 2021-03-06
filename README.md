# WHMCS module

This module enables resellers of Neostrada to sell domain registrations and transfers through WHMCS.

### Implemented features
:white_check_mark: Register, transfer and delete domains<br>
:white_check_mark: Manage a domain's nameservers<br>
:white_check_mark: Manage a domain's DNS records<br>
:white_check_mark: Update a domain's contact details<br>
:white_check_mark: Sync information about existing domains (like the expiration date)<br>
:white_check_mark: Enable/disable automatic domain renewal<br>
:white_check_mark: Get a domain's transfer token<br>
:x: Sync a domain's transfer status<br>
:x: Renew a domain<br>
:x: Lock or unlock a domain

## Installation
### Step 1
Download the [Neostrada WHMCS module](https://github.com/neostrada/whmcs/archive/master.zip). Upload the `neostrada` directory to the `/modules/registrars` directory.

### Step 2
Login to the WHMCS administration panel. Go to 'Setup > Products/Services > Domain Registrars' and look for 'Neostrada'. Click on 'Activate' and enter your API credentials.

### Step 3
Go to 'Setup > Products/Services > Domain Pricing'. Enter the extensions you want to sell to your customers and give them a price. If you want to automatically register or transfer the extension with this module, select 'Neostrada' under 'Auto Registration'.
