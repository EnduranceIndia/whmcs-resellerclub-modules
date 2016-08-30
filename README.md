Please register your email address over here: http://www.resellerclub.com/selling-options/whmcs incase you haven't already.

This will help us in keeping you posted with any updates related to our WHMCS modules.

# ResellerClub Product Modules for WHMCS

###Overview

2 types of modules for WHMCS

###### 1. Addon Module (<strong>Required<strong>)

- officialresellerclub

###### 2. Provisioning Modules

* resellerclubmdhostingXX ( Resellerclub Multi Domain Hosting - XX = us/uk/in/hk )
* resellerclubsdhostingXX ( Resellerclub Single Domain Hosting - XX = us/uk/in/hk )
* resellerclubresellerhostingXX ( Resellerclub Reseller Hosting - XX = us/uk/in/hk )
* resellerclubssl ( Resellerclub SSL Certificate )
* resellerclubvpsXX ( Resellerclub VPS - XX = us )
* resellerclubwebservices ( Resellerclub Web Services - Web site Builder and Email, etc )

###Installtion Steps

- Download the `modules` directory or Git clone the `modules` directory to root directory of WHMCS installtion.
- This should add addon module `officialresellerclub` under `<whmcs_root>/modules/addons/` directory and provisioning modules under `<whmcs_root>/modules/servers/` directory.

#### Configure Addon Module
- Login to WHMCS admin panel.
- Go to Setup > Addon Modules.
- Activate `Official Resellerclub Module`.
- Allow `Full Administrator` access to `Official Resellerclub Module`.
- Go to Addons > Official Resellerclub Module
- Enter your Reseller id and API key. 
 
#### Start using provisioning modules
- Create a product under a product group.
- Select appropriate resellerclub provisioning module on the Modules tab.
- Select a plan to associate with the product.
- It is mandatory to set price for all tenures, for which pricing is set in ResellerClub.
