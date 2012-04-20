# ResellerClub Product Modules for WHMCS

###Overview

2 types of modules for WHMCS

1. AddOn Module (<strong>Required<strong>)
2. Provisioning Modules

* resellerclubmdhosting ( Resellerclub Multi Domain Hosting )
* resellerclubsdhosting ( Resellerclub Webservices )

###Installtion Steps

- Download the `modules` directory or Git clone the `modules` directory to root directory of WHMCS installtion.
- This should add addon module `officialresellerclub` under `<whmcs_root>/modules/addons/` directory and provisioning modules under `<whmcs_root>/modules/servers/` directory.

#### Configure Addon Module
- Login to WHMCS admin panel.
- Go to Setup > Addon Modules.
- Activate `Official Resellerclub Module`.
- Allow `Full Administrator` access to `Official Resellerclub Module`.
- Go to Addons > Official Resellerclub Module
- Enter you Reseller id and Reseller password. 

#### Start using provisioning modules
- Create a product under a product group.
- Select appropriate resellerclub provisioning module on the Modules tab.
- Select a plan to associate with the product.
- It is mandatory to set price for all tenures, for which pricing is set in ResellerClub.