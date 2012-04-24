<?php

/**
 * WHMCS Module for Resellerclub's Multi Domain Hosting ( Linux / Windows )
 */

// AddOn Module Name
define( 'ADDON_MODULE_NAME' , 'officialresellerclub' );

// AddOn Module Path
$addon_module_file_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'addons' . DIRECTORY_SEPARATOR . ADDON_MODULE_NAME . DIRECTORY_SEPARATOR . ADDON_MODULE_NAME . '.php';
$addon_module_file_path = realpath( $addon_module_file_path );

 //Include AddOn Module if exists/ installed and get $resellerclub_config details
if( file_exists( $addon_module_file_path) ) {
    include_once( $addon_module_file_path );
    try {
        $resellerclub_config = _get_config_details_from_db();
    } catch (Exception $e) {
        $resellerclub_config = array();
        _display_error_block( $e->getMessage() );
    }
} else {
    _display_error_block( "Please install Official ResellerClub Addon Module '" . ADDON_MODULE_NAME . "' before using product provisioning module." );
}

/**
 *  Initialize OrderboxAPI object
 */

global $orderbox;
$orderbox =  new orderboxapi( $resellerclub_config['resellerid'] , $resellerclub_config['password'] , 'rc-whmcs-resellerclubmdhosting' );

/**
 * WHMCS provisional module core functions
 */

function resellerclubmdhosting_ConfigOptions() {
    
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

function resellerclubmdhosting_CreateAccount( $params ) {
    
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
        
        /**
         * Set cpanel auth details generated by WHMCS to blank to use the details set by Resellerclub
         */
        $sql_clear_cpanel_auth_details = "UPDATE tblhosting SET username = '', password = '' WHERE domain = '{$params['domain']}'";
        $res_clear_cpanel_auth_details = mysql_query($sql_clear_cpanel_auth_details);
        if( $res_clear_cpanel_auth_details === false ) {
            $local_api_values = array(  
                                            'serviceid' => $params['serviceid'], 
                                            'serviceusername' => ' ' ,
                                            'servicepassword' => ' ',
                                         );
            $clear_cpanel_auth_details = localAPI( 'updateclientproduct', $local_api_values , 'admin' );
        }
        
        return 'success';
        
    } catch(Exception $e ) {
        return "Customer sign up error - " . $e->getMessage();
    }
    
}

function resellerclubmdhosting_SuspendAccount( $params  ) {
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
        return "Order suspend error - " . $e->getMessage();
    }
    
}

function resellerclubmdhosting_UnsuspendAccount( $params  ) {
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
        return "Order unsuspend error - " . $e->getMessage();
    }
    
}

function resellerclubmdhosting_TerminateAccount( $params  ) {
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
        return "Order terminate error - " . $e->getMessage();
    }
}

function resellerclubmdhosting_Renew( $params  ) {

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
        return "Order renew error - " . $e->getMessage();
    }

}

function resellerclubmdhosting_ChangePackage( $params ) {
    
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

            $order_api_result = $orderbox->api( 'POST' , $api_path , $order_details );
            
            if( is_array( $order_api_result ) && strtolower( $order_api_result['status'] ) == 'error' ) {
                return $order_api_result['message'];
            }
            return 'success';
        }
    } catch (Exception $e) {
        return "Order Upgrade/Downgrade error - " . $e->getMessage();
    }
    
}

function resellerclubmdhosting_ClientAreaCustomButtonArray() {
}

function resellerclubmdhosting_AdminCustomButtonArray() {
}

function resellerclubmdhosting_ClientArea( $params ) {
        
    if( isset( $_POST['cplogin'] ) && $_POST['cplogin'] == "true" ) {
        _redirect_to_control_panel( $params );
    }
    
    global $smarty;
    global $orderbox;
    
    try {
        $is_processing = false;
        $plan_pieces = _get_plan_details( $params['configoption1']);
        if( 'windows' == $plan_pieces['type'] ) {
            $api_path = '/multidomainhosting/windows/orderid.json';
        } else {
            $api_path = '/multidomainhosting/orderid.json';
        }

        $order_id_result = $orderbox->api( 'GET' , $api_path , array( 'domain-name' => $params['domain'] ) , $response );

        if( is_array( $order_id_result ) && array_key_exists( 'status', $order_id_result ) && strtolower( $order_id_result['status'] ) == 'error'  ) {
            $is_processing = true;
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
                    $is_processing = true;
                }
            }
        }

        $smarty->assign( 'is_processing' , $is_processing );
        
        if( $is_processing ) {
            $smarty->assign('mdh_hosting_status'  , 'Your order is being processed ...');
        } else {
            if( 'windows' == $plan_pieces['type'] ) {
                $cp_url = 'http://' . $order_details['ipaddress'] . ':8880';
            } else {
                $cp_url = 'http://' . $order_details['ipaddress'] . '/cpanel';
            }
            $cp_url_href = "<a href=\"{$cp_url}\" target=\"_blank\">{$cp_url}</a>";
            $temp_url_href= "<a href=\"{$order_details['tempurl']}\" target=\"_blank\">{$order_details['tempurl']}</a>";

            $smarty->assign('mdh_hosting_status'  , $order_details['currentstatus'] );
            $smarty->assign('mdh_hosting_control_panel' ,  _display_control_panel_form() );
            $smarty->assign('mdh_hosting_temp_url' , $temp_url_href );
            $smarty->assign('mdh_hosting_cp_url' , $cp_url_href );
            $smarty->assign('mdh_hosting_cp_username' , $order_details['siteadminusername'] );
            $smarty->assign('mdh_hosting_cp_password' , $order_details['siteadminpassword'] );
            $smarty->assign('mdh_hosting_ip_address' , $order_details['ipaddress'] );
            $smarty->assign('mdh_hosting_dns_1' , $order_details['ns_detail']['0'] );
            $smarty->assign('mdh_hosting_dns_2' , $order_details['ns_detail']['1'] );
            $smarty->assign('mdh_hosting_diskspace' , $order_details['space'] == '-1' ? 'Unlimited' : $order_details['space'] );
            $smarty->assign('mdh_hosting_bandwidth' , $order_details['bandwidth'] == '-1' ? 'Unlimited' : $order_details['bandwidth'] );
        }
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
    
}

function resellerclubmdhosting_LoginLink( $params ) {
    echo "<strong>Do Not Modify</strong>" . _display_control_panel_link( $params );
}

/**
 *  Make Orderbox API Calls
 */

function _createCustomer( $params ) {
    global $orderbox;
    $customer_password = 'qwe' . rand(5000, 10000) . 'dsa';
    //TODO :: Set phone country code (phone-cc) appropriately
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
            $error = "Reseller not found at Resellerclub";
        } else {
            $reseller_branding_url = $reseller_details['brandingurl'];
        }
    }
    
    if( $error == '' ) {
        // generate authentication token
	    $ip = $_SERVER['REMOTE_ADDR'];
        $authentication_token_result = $orderbox->api( 'GET' , '/customers/generate-token.json' , array( 'username' => $params['clientsdetails']['email'] , 'passwd' => $resellerclub_customer_password , 'ip' => $ip ) , $response );

        if( is_array( $authentication_token_result ) && array_key_exists( 'status', $authentication_token_result ) && strtolower( $authentication_token_result['status'] ) == 'error' ) {
            $authentication_token = '';
        } else {
            $authentication_token = $authentication_token_result;
        }
        return $control_panel_url = "http://" . $reseller_branding_url . "/servlet/ManageServiceServletForAPI?auth-token={$authentication_token}&orderid={$order_id}";
    } else {
        throw new Exception( $error );
    }
}

/**
 * Helper functions
 */

function _get_plan_details( $plan_name ) {
    
    $plan_name_pieces = explode( '-', $plan_name );
    $plan_details['type'] = trim( strtolower( array_shift( $plan_name_pieces ) ) );
    $plan_details['id'] = trim( array_pop( $plan_name_pieces ) );
    $plan_details['name'] = trim( implode( '-' , $plan_name_pieces) );
    return $plan_details;

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
    $client_order_details = _get_order_details( $user_id , $service_id , $domain , $product_id );
    $billing_cycle = $client_order_details['products']['product'][0]['billingcycle'];
    return $billing_cycle;
}

function _get_order_duration_months( $billing_cycle ) {
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

function _redirect_to_control_panel( $params ) {
    $control_panel_url = _get_control_panel_link( $params );
    header("location: " . $control_panel_url);
    exit;    
}

function _display_control_panel_form() {
    $form_action_url = $_SERVER['REQUEST_URI'];
    $cp_form = "<form method=\"post\" action=\"{$form_action_url}\" target=\"_blank\">";	
    $cp_form .= "<input type=\"hidden\" name=\"id\" value=\"". $_POST['id'] ."\">";
    $cp_form .= "<input type=\"hidden\" name=\"cplogin\" value=\"true\">";
    $cp_form .= "<input type=\"submit\" name=\"btn_cplogin\" value=\"Login to Control Panel\">";
    $cp_form .= "</form>";
    return $cp_form;
}

?>