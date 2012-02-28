<?php

require dirname(__FILE__).DIRECTORY_SEPARATOR.'creds.php';
require dirname(__FILE__).DIRECTORY_SEPARATOR.'orderbox.php';

function resellerclubhosting_ConfigOptions() {

    $orderbox = orderbox_api_client( RESELLERCLUB_RESELLER_ID , RESELLERCLUB_RESELLER_PASSWORD );
    $plans = $orderbox( 'GET', '/products/plan-details.json' );
    
    $mdh_plans = $plans['multidomainhosting'];
    $mdh_plan_names = array();
    
    foreach ($mdh_plans as $plan) {
        $mdh_plan_names[] = $plan['plan_name'];
    }
    
    $configarray = array(
     "Hosting Plan" => array( "Type" => "dropdown", "Options" => implode(',', $mdh_plan_names) , "Description" => "Select a hosting plan to associate with this product"),
	);

	return $configarray;

}


?>