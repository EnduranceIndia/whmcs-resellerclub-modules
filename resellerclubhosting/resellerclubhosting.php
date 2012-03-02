<?php
require dirname(__FILE__).DIRECTORY_SEPARATOR.'creds.php';
require dirname(__FILE__).DIRECTORY_SEPARATOR.'orderboxapi.class.php';

$orderboxapi =  new orderboxapi( RESELLERCLUB_RESELLER_ID , RESELLERCLUB_RESELLER_PASSWORD );

function resellerclubhosting_ConfigOptions() {
    
    global $orderboxapi;
    $mdh_plan_names = array();
    
    $plans = $orderboxapi->call( 'GET', '/products/plan-details.json' );
    
    if( array_key_exists( 'multidomainhosting', $plans )  ) {
        $mdh_linux_plans = $plans['multidomainhosting'];
        foreach ($mdh_linux_plans as $plan_id => $plan) {
            $mdh_plan_names[] = 'Linux ' . ' - ' . $plan['plan_name'] . ' - ' . $plan_id;
        }
    }
    
    if( array_key_exists( 'multidomainwindowshosting', $plans )  ) {
        $mdh_windows_plans = $plans['multidomainwindowshosting'];
        foreach ($mdh_windows_plans as $plan_id => $plan) {
            $mdh_plan_names[] = 'Windows ' . ' - ' . $plan['plan_name'] . ' - ' . $plan_id;
        }
    }
    
    $configarray = array(
     "Hosting Plan" => array( "Type" => "dropdown", "Options" => implode(',', $mdh_plan_names) , "Description" => "Select a hosting plan to associate with this product"),
    );

    return $configarray;
}

function resellerclubhosting_CreateAccount( $params ) {
    global $orderboxapi;
    try {
        $client_details = $orderboxapi->call( 'GET' , '/customers/details.json' , array( 'username' => $params['clientsdetails']['email'] ) , $response );
        
        if( is_array($client_details) && strtolower( $client_details['status'] ) == 'error' ) {
            // customer not found - create a new one - get customer id of newly created customer

            $customer_password = 'qwe' . rand(5000, 10000) . 'dsa';
            
            $customer_details = array(      'username' => $params['clientsdetails']['email'], 
                                                        'passwd' => $customer_password, 
                                                        'name' => $params['clientsdetails']['firstname'] . ' ' . $params['clientsdetails']['lastname'], 
                                                        'company' => strlen(trim($params['clientsdetails']['companyname'])) ? $params['clientsdetails']['companyname'] : '-', 
                                                        'address-line-1' => $params['clientsdetails']['address1'], 
                                                        'address-line-2' => $params['clientsdetails']['address2'], 
                                                        'address-line-3' => '', 
                                                        'city' => $params['clientsdetails']['city'], 
                                                        'state' => $params['clientsdetails']['state'], 
                                                        'country' => $params['clientsdetails']['country'], 
                                                        'zipcode' => $params['clientsdetails']['postcode'], 
                                                        'phone-cc' => '1',     //phonenumber - country code
                                                        'phone' => $params['clientsdetails']['phonenumber'], 
                                                        'lang-pref' => 'en'
                                                    );
            
            $customer_signup_result = $orderboxapi->call( 'POST' , '/customers/signup.json' , $customer_details , $response_headers );
            $resellerclub_customer_id = $customer_signup_result;
            
        } else {
            // get customer id
            $resellerclub_customer_id = $client_details['customerid'];
        }

        //  add new order in resellerclub
        $plan_name_pieces = explode( '-', $params['configoption1']);
        $plan_id = trim( array_pop( $plan_name_pieces ) );
        if( 'windows' == strtolower( trim( $plan_name_pieces[0] ) ) ) {
            $api_path = '/multidomainhosting/windows/add.json';
        } else {
            $api_path = '/multidomainhosting/add.json';
        }

        $local_api_values = array(  
                                            'clientid' => $params['clientsdetails']['userid'] , 
                                            'serviceid' => $params['serviceid'], 
                                            'domain' => $params['domain'] , 
                                            'pid' => $params['pid'] 
                                         );
        $client_order_details = localAPI( 'getclientsproducts', $local_api_values , 'admin' );
        $billing_cycle = $client_order_details['products']['product'][0]['billingcycle'];
        $billing_cycle = strtolower( $billing_cycle );
        switch( $billing_cycle ) {
            case 'quarterly': $months = 3; break;
            case 'semi-annually': $months = 6; break;
            case 'annually': $months = 12; break;
            case 'biennially': $months = 24; break;
            case 'triennially': $months = 36; break;
            default: $months = 1; break;
        }

        $order_details = array(    'domain-name' => $params['domain'],
                                             'customer-id' => $resellerclub_customer_id,
                                             'months' => $months,
                                             'invoice-option' => 'NoInvoice',
                                             'plan-id' => $plan_id,
                                             'enable-ssl' => true
                                        );

        $order_api_result = $orderboxapi->call( 'POST' , $api_path , $order_details );

        if( is_array( $order_api_result ) && strtolower( $order_api_result['status'] ) == 'error' ) {
            return $order_api_result['message'];
        }
        
        return 'success';
        
    } catch(Exception $e ) {
        echo "Exception :: Customer Sign up / Add Order" . $e->getMessage();
    }
    
}

?>