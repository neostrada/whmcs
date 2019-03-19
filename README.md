# WHMCS module

This module enables resellers of Neostrada to sell domain registrations and transfers through WHMCS.

### Disclamer
This module is currently in beta. It is strongly recommended to use this module in your test environment only. Using it in production is at your own risk.

## Installation
### Step 1
Download the [Neostrada WHMCS module](https://github.com/neostrada/whmcs/archive/master.zip). Upload the `neostrada` directory to the `/modules/registrars` directory.

### Step 2
Login to the WHMCS administration panel. Go to 'Setup > Products/Services > Domain Registrars' and look for 'Neostrada'. Click on 'Activate' and enter your API credentials.

### Step 3
Go to 'Setup > Products/Services > Domain Pricing'. Enter the extensions you want to sell to your customers and give them a price. If you want to automatically register or transfer the extension with this module, select 'Neostrada' under 'Auto Registration'.
