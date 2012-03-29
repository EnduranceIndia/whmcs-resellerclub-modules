<?php
require dirname(__FILE__).DIRECTORY_SEPARATOR.'creds.php';
require dirname(__FILE__).DIRECTORY_SEPARATOR.'orderboxapi.class.php';

define( 'ADDON_MODULE_NAME' , 'officialresellerclub' );
global $orderbox;
$orderbox =  new orderboxapi( RESELLERCLUB_RESELLER_ID , RESELLERCLUB_RESELLER_PASSWORD );

$addon_module_file_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'addons' . DIRECTORY_SEPARATOR . ADDON_MODULE_NAME . DIRECTORY_SEPARATOR . ADDON_MODULE_NAME . '.php';

echo " :: " . var_dump( dirname(__FILE__) );

echo "before";
var_dump( $addon_module_file_path );


$addon_module_file_path = realpath( $addon_module_file_path );

echo "after";
var_dump( $addon_module_file_path );

function addOnModule_exists( $addon_module_file_path ) {
    return file_exists($addon_module_file_path);
}

if( addOnModule_exists( $addon_module_file_path) ) {
    include_once( $addon_module_file_path );
    echo "yea";
} else {
    echo "<div style=\"color: #ff0000;\">Please install Official ResellerClub Addon Module '" . ADDON_MODULE_NAME . "' before using product provisioning module.</div>";
}

/*
 *  core functions
 */

function resellerclubhosting_ConfigOptions() {
    
    global $orderbox;
    $mdh_plan_names = array();
    
    $plans = $orderbox->api( 'GET', '/products/plan-details.json' );
    
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
    
    global $orderbox;
    
    try {
        $client_details = $orderbox->api( 'GET' , '/customers/details.json' , array( 'username' => $params['clientsdetails']['email'] ) , $response );
        
        if( is_array($client_details) && strtolower( $client_details['status'] ) == 'error' ) {
            $resellerclub_customer_id = _createCustomer( $params );
        } else {
            $resellerclub_customer_id = $client_details['customerid'];
        }
        
        //  add new order in resellerclub
        $plan_pieces = _get_plan_details( $params['configoption1']);
        if( 'windows' == $plan_pieces['type'] ) {
            $api_path = '/multidomainhosting/windows/add.json';
        } else {
            $api_path = '/multidomainhosting/add.json';
        }

        $billing_cycle = _get_order_billing_cycle( $params['clientsdetails']['userid'] , $params['serviceid'] , $params['domain'] , $params['pid']  );
        $billing_cycle = strtolower( $billing_cycle );
        $months =_get_order_duration_months( $billing_cycle );

        $order_details = array(    'domain-name' => $params['domain'],
                                             'customer-id' => $resellerclub_customer_id,
                                             'months' => $months,
                                             'invoice-option' => 'NoInvoice',
                                             'plan-id' => $plan_pieces['id'],
                                             'enable-ssl' => true
                                        );

        $order_api_result = $orderbox->api( 'POST' , $api_path , $order_details );

        if( is_array( $order_api_result ) && strtolower( $order_api_result['status'] ) == 'error' ) {
            return $order_api_result['message'];
        }
        
        //set WHMCS generated cpanel username & password to blank
        $local_api_values = array(  
                                        'serviceid' => $params['serviceid'], 
                                        'serviceusername' => ' ' ,
                                        'servicepassword' => ' ',
                                     );
        $clear_cpanel_auth_details = localAPI( 'updateclientproduct', $local_api_values , 'admin' );
        
        return 'success';
        
    } catch(Exception $e ) {
        return "Error :: Customer Sign up / Add Order : " . $e->getMessage();
    }
    
}

function resellerclubhosting_SuspendAccount( $params  ) {
    global $orderbox;
    
    //TODO :: REMOVE THIS HACK FOR DEMO RESELLER ACCOUNT
    if( RESELLERCLUB_RESELLER_ID == '204860' ) {
        $params['domain'] = $params['domain'] . '.onlyfordemo.com';
    }
    
    try {
        
        $plan_pieces = _get_plan_details( $params['configoption1']);
        if( 'windows' == $plan_pieces['type'] ) {
            $api_path = '/multidomainhosting/windows/orderid.json';
        } else {
            $api_path = '/multidomainhosting/orderid.json';
        }
        
         $order_id_result = $orderbox->api( 'GET' , $api_path , array( 'domain-name' => $params['domain'] ) , $response );
    
        if( is_array( $order_id_result ) && array_key_exists( 'status', $order_id_result ) && strtolower( $order_id_result['status'] ) == 'error'  ) {
            return $order_id_result['message'];
        } else {
            $order_id = $order_id_result;
            
            $order_suspend_result = $orderbox->api( 'POST' , '/orders/suspend.json' , array( 'order-id' => $order_id , 'reason' => $params['suspendreason'] ) , $response );

            if( is_array( $order_suspend_result ) && array_key_exists( 'status', $order_suspend_result ) ) {
                $status =  strtolower( $order_suspend_result['status'] );
                if( $status  == 'success' ) {
                    return 'success';
                } else {
                    return $order_suspend_result['message'];
                }
            }
        }
    } catch ( Exception $e ) {
        return "Error :: Order Suspend :  " . $e->getMessage();
    }
    
}

function resellerclubhosting_UnsuspendAccount( $params  ) {
    global $orderbox;
    
    //TODO :: REMOVE THIS HACK FOR DEMO RESELLER ACCOUNT
    if( RESELLERCLUB_RESELLER_ID == '204860' ) {
        $params['domain'] = $params['domain'] . '.onlyfordemo.com';
    }
    
    try {
        
        $plan_pieces = _get_plan_details( $params['configoption1']);
        if( 'windows' == $plan_pieces['type'] ) {
            $api_path = '/multidomainhosting/windows/orderid.json';
        } else {
            $api_path = '/multidomainhosting/orderid.json';
        }
        
         $order_id_result = $orderbox->api( 'GET' , $api_path , array( 'domain-name' => $params['domain'] ) , $response );
    
        if( is_array( $order_id_result ) && array_key_exists( 'status', $order_id_result ) && strtolower( $order_id_result['status'] ) == 'error'  ) {
            return $order_id_result['message'];
        } else {
            $order_id = $order_id_result;

            $order_unsuspend_result = $orderbox->api( 'POST' , '/orders/unsuspend.json' , array( 'order-id' => $order_id ) , $response );
            
            if( is_array( $order_unsuspend_result ) && array_key_exists( 'status', $order_unsuspend_result ) ) {
                $status =  strtolower( $order_unsuspend_result['status'] );
                if( $status  == 'success' ) {
                    return 'success';
                } else {
                    return $order_unsuspend_result['message'];
                }
            }
        }
    } catch ( Exception $e ) {
        return "Error :: Order Unsuspend :  " . $e->getMessage();
    }
    
}

function resellerclubhosting_TerminateAccount( $params  ) {
    global $orderbox;
    
    //TODO :: REMOVE THIS HACK FOR DEMO RESELLER ACCOUNT
    if( RESELLERCLUB_RESELLER_ID == '204860' ) {
        $params['domain'] = $params['domain'] . '.onlyfordemo.com';
    }
    
    try {
        
        $plan_pieces = _get_plan_details( $params['configoption1']);
        if( 'windows' == $plan_pieces['type'] ) {
            $api_path = '/multidomainhosting/windows/orderid.json';
        } else {
            $api_path = '/multidomainhosting/orderid.json';
        }        
        
         $order_id_result = $orderbox->api( 'GET' , $api_path , array( 'domain-name' => $params['domain'] ) , $response );
    
        if( is_array( $order_id_result ) && array_key_exists( 'status', $order_id_result ) && strtolower( $order_id_result['status'] ) == 'error'  ) {
            return $order_id_result['message'];
        } else {
            $order_id = $order_id_result;

            $plan_pieces = _get_plan_details( $params['configoption1']);
            if( 'windows' == $plan_pieces['type'] ) {
                $api_path = '/multidomainhosting/windows/delete.json';
            } else {
                $api_path = '/multidomainhosting/delete.json';
            }
            
            $order_delete_result = $orderbox->api( 'POST' , $api_path , array( 'order-id' => $order_id ) , $response );
            
            if( is_array( $order_delete_result ) && array_key_exists( 'status', $order_delete_result ) ) {
                $status =  strtolower( $order_delete_result['status'] );
                if( $status  == 'success' ) {
                    return 'success';
                } else {
                    return $order_delete_result['message'];
                }
            }
        }
    } catch ( Exception $e ) {
        return "Error :: Order Terminate :  " . $e->getMessage();
    }
}

function resellerclubhosting_Renew( $params  ) {

    global $orderbox;
    
    //TODO :: REMOVE THIS HACK FOR DEMO RESELLER ACCOUNT
    if( RESELLERCLUB_RESELLER_ID == '204860' ) {
        $params['domain'] = $params['domain'] . '.onlyfordemo.com';
    }
    
    try {
        
        $plan_pieces = _get_plan_details( $params['configoption1']);
        if( 'windows' == $plan_pieces['type'] ) {
            $api_path = '/multidomainhosting/windows/orderid.json';
        } else {
            $api_path = '/multidomainhosting/orderid.json';
        }  
         $order_id_result = $orderbox->api( 'GET' , $api_path , array( 'domain-name' => $params['domain'] ) , $response );
    
        if( is_array( $order_id_result ) && array_key_exists( 'status', $order_id_result ) && strtolower( $order_id_result['status'] ) == 'error'  ) {
            return $order_id_result['message'];
        } else {
            $order_id = $order_id_result;

            $plan_pieces = _get_plan_details( $params['configoption1']);
            if( 'windows' == $plan_pieces['type'] ) {
                $api_path = '/multidomainhosting/windows/renew.json';
            } else {
                $api_path = '/multidomainhosting/renew.json';
            }
            
            $billing_cycle = _get_order_billing_cycle( $params['clientsdetails']['userid'] , $params['serviceid'] , $params['domain'] , $params['pid']  );
            $billing_cycle = strtolower( $billing_cycle );
            $months =_get_order_duration_months( $billing_cycle );    
            
            $order_details = array(    'order-id' => $order_id,
                                                 'months' => $months,
                                                 'invoice-option' => 'NoInvoice',
                                                 'enable-ssl' => true
                                            );

            $order_api_result = $orderbox->api( 'POST' , $api_path , $order_details );            

            if( is_array( $order_api_result ) && strtolower( $order_api_result['status'] ) == 'error' ) {
                return $order_api_result['message'];
            }

            return 'success';
        }
    } catch ( Exception $e ) {
        return "Error :: Order Renew :  " . $e->getMessage();
    }

}

function resellerclubhosting_ChangePackage( $params ) {
    
    global $orderbox;
    
    try {
        $plan_pieces = _get_plan_details( $params['configoption1']);
        if( 'windows' == $plan_pieces['type'] ) {
            $api_path = '/multidomainhosting/windows/orderid.json';
        } else {
            $api_path = '/multidomainhosting/orderid.json';
        }
        $order_id_result = $orderbox->api( 'GET' , $api_path , array( 'domain-name' => $params['domain'] ) , $response );
//echo "Result of Order ID API call";
//var_dump($order_id_result);
        if( is_array( $order_id_result ) && array_key_exists( 'status', $order_id_result ) && strtolower( $order_id_result['status'] ) == 'error'  ) {
            return $order_id_result['message'];
        } 
        else {
            $order_id = $order_id_result;
            
            if( 'windows' == $plan_pieces['type'] ) {
                $api_path = '/multidomainhosting/windows/modify.json';
            } else {
                $api_path = '/multidomainhosting/modify.json';
            }
            
            $billing_cycle = _get_order_billing_cycle( $params['clientsdetails']['userid'] , $params['serviceid'] , $params['domain'] , $params['pid']  );
            $billing_cycle = strtolower( $billing_cycle );
            $months =_get_order_duration_months( $billing_cycle );    
            
            $order_details = array(    'order-id' => $order_id,
                                                 'new-plan-id' => $plan_pieces['id'],
                                                 'months' => $months,
                                                 'invoice-option' => 'NoInvoice',
                                            );

//echo "<pre>Order Details for Upgrade Call";
//print_r($order_details);

            $order_api_result = $orderbox->api( 'POST' , $api_path , $order_details );     
//echo "Upgrade Call Result";
//print_r($order_api_result);
//exit;
            if( is_array( $order_api_result ) && strtolower( $order_api_result['status'] ) == 'error' ) {
                return $order_api_result['message'];
            }

            return 'success';
        }
    } catch (Exception $e) {
        return "Error :: Order Upgrade/Downgrade :  " . $e->getMessage();
    }
    
}

//function resellerclubhosting_ClientAreaCustomButtonArray() {
//    return array( "From ClientAreaCustomButtonArray" => "ClientArea" );
//}

function resellerclubhosting_AdminCustomButtonArray() {
    return array( "MAN Renew" => "Renew" );
}

function resellerclubhosting_ClientArea( $params ) {
        
    if( isset( $_POST['cplogin'] ) && $_POST['cplogin'] == "true" ) {
        _redirect_to_control_panel( $params );
    }
    
    global $smarty;
//    $smarty->assign("moduleclientarea", "Module Client Area Main Variable");
//    $smarty->assign("modulecustombuttonresult", "modulecustombuttonresult");

    global $orderbox;
    try {
        
        $plan_pieces = _get_plan_details( $params['configoption1']);
        if( 'windows' == $plan_pieces['type'] ) {
            $api_path = '/multidomainhosting/windows/orderid.json';
        } else {
            $api_path = '/multidomainhosting/orderid.json';
        }

        $order_id_result = $orderbox->api( 'GET' , $api_path , array( 'domain-name' => $params['domain'] ) , $response );

        if( is_array( $order_id_result ) && array_key_exists( 'status', $order_id_result ) && strtolower( $order_id_result['status'] ) == 'error'  ) {
            return $order_id_result['message'];
        } else {
            $order_id = $order_id_result;
            if( 'windows' == $plan_pieces['type'] ) {
                $api_path = '/multidomainhosting/windows/details.json';
            } else {
                $api_path = '/multidomainhosting/details.json';
            }

            $order_details = $orderbox->api( 'GET' , $api_path , array( 'order-id' => $order_id ) , $response );

            if( is_array( $order_details ) && array_key_exists( 'status', $order_details ) ) {
                $status =  strtolower( $order_details['status'] );
                if( $status  != 'success' ) {
                    return $order_suspend_result['message'];
                }
            }
        }

        if( 'windows' == $plan_pieces['type'] ) {
            //TODO :: insert Plesk link here
            $cp_url = 'http://' . $order_details['ipaddress'] . '/cpanel';
        } else {
            $cp_url = 'http://' . $order_details['ipaddress'] . '/cpanel';
        }
        
        $smarty->assign('mdh_hosting_status'  , $order_details['currentstatus'] );
//        $smarty->assign('mdh_hosting_control_panel' ,  _display_control_panel_link( $params ) );
        $smarty->assign('mdh_hosting_control_panel' ,  _display_control_panel_form() );
        $smarty->assign('mdh_hosting_temp_url' , $order_details['tempurl'] );
        $smarty->assign('mdh_hosting_cp_url' , $cp_url );
        $smarty->assign('mdh_hosting_cp_username' , $order_details['siteadminusername'] );
        $smarty->assign('mdh_hosting_cp_password' , $order_details['siteadminpassword'] );
        $smarty->assign('mdh_hosting_ip_address' , $order_details['ipaddress'] );
        $smarty->assign('mdh_hosting_dns_1' , $order_details['ns_detail']['0'] );
        $smarty->assign('mdh_hosting_dns_2' , $order_details['ns_detail']['1'] );
        $smarty->assign('mdh_hosting_diskspace' , $order_details['space'] == '-1' ? 'Unlimited' : $order_details['space'] );
        $smarty->assign('mdh_hosting_bandwidth' , $order_details['bandwidth'] == '-1' ? 'Unlimited' : $order_details['bandwidth'] );        
        
        
//        $template_vars = array(
//                                        'mdh_hosting_status'    =>  $order_details['currentstatus'],
//                                        'mdh_hosting_control_panel'  =>  _display_control_panel_link( $params ),
//                                        'mdh_hosting_temp_url'  =>  $order_details['tempurl'],
//                                        'mdh_hosting_cp_url'  =>  $cp_url,
//                                        'mdh_hosting_cp_username'  =>  $order_details['siteadminusername'],
//                                        'mdh_hosting_cp_password'  =>  $order_details['siteadminpassword'],
//                                        'mdh_hosting_ip_address'  =>  $order_details['ipaddress'],
//                                        'mdh_hosting_dns_1'  =>  $order_details['ns_detail']['0'],
//                                        'mdh_hosting_dns_2'  =>  $order_details['ns_detail']['1'],
//                                        'mdh_hosting_diskspace'  =>  $order_details['space'] == '-1' ? 'Unlimited' : $order_details['space'],
//                                        'mdh_hosting_bandwidth'  =>  $order_details['bandwidth'] == '-1' ? 'Unlimited' : $order_details['bandwidth'],
//                                    );
//
//        extract($template_vars);
//        ob_start();
//        include_once 'templates'.DIRECTORY_SEPARATOR.'clientarea.php';
//        $clientarea_data = ob_get_contents();
//        ob_end_clean();
//
//        return $clientarea_data;
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
    
}

function resellerclubhosting_LoginLink( $params ) {
    echo "login link " . _display_control_panel_link( $params );
}

/*
 *  custom functions
 */

//function resellerclubhosting_ManualRenew( $params  ) {
//    var_dump($params); exit;
//}

function _createCustomer( $params ) {
    global $orderbox;
    $customer_password = 'qwe' . rand(5000, 10000) . 'dsa';
    $customer_details = array(      
                                            'username' => $params['clientsdetails']['email'], 
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

    $create_customer_result = $orderbox->api( 'POST' , '/customers/signup.json' , $customer_details , $response_headers );

    if( is_array($create_customer_result) && strtolower( $create_customer_result['status'] ) == 'error' ) {
        throw new Exception( $create_customer_result['message'] );
    }
    return $create_customer_result;
    
}

function _get_plan_details( $plan_name ) {
    
    $plan_details['type'] = trim( substr( $plan_name, 0, strpos( $plan_name , "-" ) ) );
    $plan_details['id'] = trim( substr( $plan_name, strrpos( $plan_name , "-" ) + 1 ) );
    $plan_details['name'] = trim( substr( $plan_name, strpos( $plan_name , "-" ) + 1, strrpos( $plan_name , "-" )  - strpos( $plan_name , "-" ) - 1 ) );
    return $plan_details;
    
//    $plan_name_pieces = explode( '-', $plan_name );
//    foreach( $plan_name_pieces as $key => &$val ) {
////        $val = trim($val);
//        if( $key == 0 ) {
//            $plan_details['type'] = strtolower( $val );
//        } else if( $key == ( count($plan_name_pieces) - 1 ) ) {
//            $plan_details['id'] = $val;
//        }
//        else {
//            $plan_details['name'] .= $val . '-';
//        }
////        switch ( $key ) {
////            case 0: $plan_details['type'] = strtolower( $val ); break;
////            case 1: $plan_details['name'] = $val; break;
////            case 2: $plan_details['id'] = $val; break;
////            default: break;
////        }
//    }
//    return $plan_details;
}

function _get_order_details( $user_id , $service_id , $domain , $product_id ) {
        $local_api_values = array(  
                                        'clientid' => $user_id, 
                                        'serviceid' => $service_id, 
                                        'domain' => $domain , 
                                        'pid' => $product_id 
                                     );
        $client_order_details = localAPI( 'getclientsproducts', $local_api_values , 'admin' );
        
        return $client_order_details;
}

function _get_order_billing_cycle( $user_id , $service_id , $domain , $product_id ) {
    
    if( RESELLERCLUB_RESELLER_ID == '204860' ) {
        $domain =  strstr( $domain , ".onlyfordemo.com" , true );
    }
    
    $client_order_details = _get_order_details( $user_id , $service_id , $domain , $product_id );
    $billing_cycle = $client_order_details['products']['product'][0]['billingcycle'];
    return $billing_cycle;
}

function _get_order_duration_months( $billing_cycle ) {
//    $months = 1;
    switch( $billing_cycle ) {
        case 'quarterly': $months = 3; break;
        case 'semi-annually': $months = 6; break;
        case 'annually': $months = 12; break;
        case 'biennially': $months = 24; break;
        case 'triennially': $months = 36; break;
        default: $months = 1; break;
    }
    return $months;
}

function _display_control_panel_link( $params ) {
    
    try {
        $control_panel_url = _get_control_panel_link( $params );
        return "<input type='button' name='custom_control_panel_login' value='Login to Control Panel' onclick='javascript:window.open(\"{$control_panel_url}\")' />";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        return "<input type='button' name='custom_control_panel_login' value='Login to Control Panel' onclick='javascript:alert(\"{$error_message}\");' />";
    }
    
}

function _get_control_panel_link( $params ) {
    
    global $orderbox;
    $error = '';
    
    // get customer password from resellerclub
    $client_details = $orderbox->api( 'GET' , '/customers/details.json' , array( 'username' => $params['clientsdetails']['email'] ) , $response );
    if( is_array($client_details) && strtolower( $client_details['status'] ) == 'error' ) {
        $error = "Customer ({$params['clientsdetails']['email']}) not found at Resellerclub";
    } else {
        $resellerclub_customer_password = $client_details['password'];
    }
    
    if( $error == '' ) {
        // get orderid from resellerclub
        
        //TODO :: REMOVE THIS HACK FOR DEMO RESELLER ACCOUNT
        if( RESELLERCLUB_RESELLER_ID == '204860' ) {
            $params['domain'] = $params['domain'] . '.onlyfordemo.com';
        }
        
        $plan_pieces = _get_plan_details( $params['configoption1']);
        if( 'windows' == $plan_pieces['type'] ) {
            $api_path = '/multidomainhosting/windows/orderid.json';
        } else {
            $api_path = '/multidomainhosting/orderid.json';
        }
        $order_id_result = $orderbox->api( 'GET' , $api_path , array( 'domain-name' => $params['domain'] ) , $response );

        if( is_array( $order_id_result ) && array_key_exists( 'status', $order_id_result ) && strtolower( $order_id_result['status'] ) == 'error'  ) {
            $error = "Hosting order ({$params['domain']}) not found at Resellerclub";
        } else {
            $order_id = $order_id_result;
        }
    }
    
     if( $error == '' ) {
        // get reseller branded url
        $reseller_details = $orderbox->api( 'GET' , '/resellers/details.json' , array( ) , $response );
        if( is_array($reseller_details) && strtolower( $reseller_details['status'] ) == 'error' ) {
            $error = "Reseller (".RESELLERCLUB_RESELLER_ID.") not found at Resellerclub";
        } else {
            $reseller_branding_url = $reseller_details['brandingurl'];
        }
    }
    
    if( $error == '' ) {
        // generate authentication token
        // TODO :: remove this piece of code 
        // this is to test from localhost
        if( $_SERVER['REMOTE_ADDR'] == '127.0.0.1' ) {
            $ip = '59.162.86.164';
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $authentication_token_result = $orderbox->api( 'GET' , '/customers/generate-token.json' , array( 'username' => $params['clientsdetails']['email'] , 'passwd' => $resellerclub_customer_password , 'ip' => $ip ) , $response );
        if( is_array( $authentication_token_result ) && array_key_exists( 'status', $authentication_token_result ) && strtolower( $authentication_token_result['status'] ) == 'error' ) {
            //TODO :: Handle error here
            $authentication_token = '';
        } else {
            $authentication_token = $authentication_token_result;
        }

        // generate login link
        // input : reseller branded url, authentication token, order ID
        return $control_panel_url = "http://" . $reseller_branding_url . "/servlet/ManageServiceServletForAPI?auth-token={$authentication_token}&orderid={$order_id}";
        //return "<input type='button' name='custom_control_panel_login' value='Login to Control Panel' onclick='javascript:window.open(\"{$control_panel_url}\")' />";
//        return "<input type='button' name='custom_control_panel_login' value='Login to Control Panel' target='_blank' href='' />";
    } else {
//        return "<input type='button' name='custom_control_panel_login' value='Login to Control Panel' onclick='javascript:alert(\"{$error}\");' />";
        throw new Exception( $error );
    }
}

function _redirect_to_control_panel( $params ) {
    $control_panel_url = _get_control_panel_link( $params );
    header("location: " . $control_panel_url);
    exit;    
}

function _display_control_panel_form() {
    $cp_form = "<form method=\"post\" action=\"/clientarea.php?action=productdetails\" target=\"_blank\">";
    $cp_form .= "<input type=\"hidden\" name=\"id\" value=\"". $_POST['id'] ."\">";
    $cp_form .= "<input type=\"hidden\" name=\"cplogin\" value=\"true\">";
    $cp_form .= "<input type=\"submit\" name=\"btn_cplogin\" value=\"Login to Control Panel\">";
    $cp_form .= "</form>";
    return $cp_form;
}

/**
 *  make orderbox calls
 */

?>