<?php

require dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'orderboxapi.class.php';

define('RCLUB_ADDON_DB_TABLE' , 'mod_resellerclub');

function officialresellerclub_config() {
    return array(
        'name' => 'Official Resellerclub Module',
        'version' => '1.5',
        'author' => 'Resellerclub',
        'description' => 'An official addon module from Resellerclub for WHMCS to manage all provisioning modules for Resellerclub',
        'language'  =>  'english',
    );
}

function officialresellerclub_activate() {
    $module_activate_query = "CREATE TABLE `".RCLUB_ADDON_DB_TABLE."` (`id` INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`config` VARCHAR( 16 ) NOT NULL ,`value` TEXT NOT NULL )";
    $module_activate_result = mysql_query($module_activate_query);
    if( $module_activate_result === false ) {
        return array('status'=> 'error','description'=> mysql_error() );
    } else {
        return array('status'=> 'info','description'=> 'Please fill in the details below the list of addon modules.' );
    }
}

function officialresellerclub_deactivate() {
    $module_deactivate_query = "DROP TABLE `".RCLUB_ADDON_DB_TABLE."`";
    $module_deactivate_result = mysql_query($module_deactivate_query);
    if( $module_deactivate_result === false ) {
        return array('status'=> 'error','description'=> mysql_error() );
    } else {
        return array('status'=> 'success','description'=> 'Thank you for using Resellerclub module for WHMCS.' );
    }
}

function officialresellerclub_output( $vars ) {
    $modulelink = $vars['modulelink'];

    try {
        if( isset( $_POST['action'] ) && strlen( $_POST['action'] ) > 0 && $_POST['action'] == 'saveconfig'  ) {
            _save_config_details();
        }
    } catch (Exception $e) {
        echo "Exception : " . $e->getMessage();
    }

    _show_tab_config_contents( $modulelink );

}

function officialresellerclub_sidebar( $vars ) {
    $sidebar = '';
    return $sidebar;
}

function _show_tab_config_contents( $modulelink ) {
    $config = _get_config_details_from_db();

    if( is_array( $config ) && isset( $config['resellerid'] ) && strlen( $config['resellerid'] ) > 0 && isset( $config['apikey'] ) && strlen( $config['apikey'] ) > 0 ) {
        if( $_POST['action'] != 'saveconfig' ) {
            //$message = "Welcome {$config['resellerid']} !";
            //_display_success_block( $message );
        }
    } else {
        if( $_POST['action'] != 'saveconfig' ) {
            $message = "Please enter reseller id and apikey";
            _display_error_block( $message );
        }
    }
    $resellerid = isset( $config['resellerid'] ) ? $config['resellerid'] : '';
    $apikey = isset( $config['apikey'] ) ? $config['apikey'] : '';
    $enable_log_checked_text = $config['enable_log'] != "0" ? 'checked="checked"' : '';

    $tpl_vars = array('resellerid' => $resellerid, 'apikey' => $apikey, 'formaction' => $modulelink, 'enable_log_checked_text' => $enable_log_checked_text);
    extract( $tpl_vars );
    include_once( dirname(__FILE__) . '/templates/tpl_officialresellerclub_config.php' );

}

function _get_config_details_from_db( ) {
    $error = '';
    $config = array();
    $sql = "select * from ".RCLUB_ADDON_DB_TABLE;
    $res = mysql_query($sql);
    if( $res !== false ) {
        $config_count = mysql_num_rows( $res );
        if( $config_count > 0 ) {
            while( $row = mysql_fetch_assoc( $res ) ) {
                $config[$row['config']] = $row['value'];
            }
        }
    } else {
        $error = mysql_error();
    }
    if( strlen($error) == 0 ) {
        return $config;
    } else {
        throw new Exception( $error );
    }
}

function _save_config_details() {

    $reseller_id = $_POST['resellerid'];
    $apikey = htmlspecialchars_decode( $_POST['apikey'] );
    $enable_log = ( array_key_exists('enable_log', $_POST) && $_POST['enable_log'] == 1 ) ? 1 : 0;

    $config = _get_config_details_from_db();

    if( is_array( $config ) && count( $config ) > 0 ) {
        if( _check_resellerclub_credentials( $reseller_id , $apikey ) ) {
            $sql_update_config_details_resellerid = "UPDATE `".RCLUB_ADDON_DB_TABLE."` SET value = '{$reseller_id}' WHERE config = 'resellerid'";
            $update_resellerid_res = mysql_query( $sql_update_config_details_resellerid );
            if( $update_resellerid_res == false ) {
                throw new Exception( mysql_error() );
            }

            $apikey = mysql_real_escape_string( $apikey );
            $sql_update_config_details_apikey = "UPDATE `".RCLUB_ADDON_DB_TABLE."` SET value = '{$apikey}' WHERE config = 'apikey'";
            $update_apikey_res = mysql_query( $sql_update_config_details_apikey );
            if( $update_apikey_res == false ) {
                throw new Exception( mysql_error() );
            }

            $sql_update_config_details_log_flag = "UPDATE `".RCLUB_ADDON_DB_TABLE."` SET value = '{$enable_log}' WHERE config = 'enable_log'";
            $update_log_flag_res = mysql_query( $sql_update_config_details_log_flag );
            if( $update_log_flag_res == false ) {
                throw new Exception( mysql_error() );
            }
        } else {
            $message = "Incorrect credentials for {$reseller_id}.";
            _display_error_block( $message );
            return false;
        }
    } else {
        if( strlen( trim( $reseller_id ) ) == 0 || strlen( trim( $apikey ) ) == 0 ) {
            return false;
        }
        else if( _check_resellerclub_credentials( $reseller_id , $apikey ) ) {
            $apikey = mysql_real_escape_string( $apikey );
            $sql_insert_config_details = "INSERT INTO `".RCLUB_ADDON_DB_TABLE."` ( config, value ) VALUES ( 'resellerid' , '{$reseller_id}'), ( 'apikey' , '{$apikey}'), ( 'enable_log' , '{$enable_log}')";
            $insert_res = mysql_query( $sql_insert_config_details );
            if( $insert_res == false ) {
                throw new Exception( mysql_error() );
            }
        } else {
            $message = "Invalid credentials for {$reseller_id} ";
            _display_error_block( $message );
            return false;
        }
    }
    $message = "Reseller details saved successfully.";
    _display_success_block( $message );
    return true;
}

function _check_resellerclub_credentials( $reseller_id , $apikey ) {
    $orderbox =  new orderboxapi( $reseller_id , $apikey );
    $reseller_details = $orderbox->api( 'GET' , '/resellers/details.json' , array() , $response );
    if( is_array($reseller_details) && array_key_exists('resellerid',$reseller_details) && $reseller_details['resellerid'] == $reseller_id  ) {
        return true;
    } else {
        return false;
    }
}

function _display_error_block( $message ) {
    echo "<div style=\"color: #F00000; height: 25px; width: 400px; text-align:center; background-color:transparent; margin: 10px auto; border: 1px dashed #e00;\">{$message}</div>";
}

function _display_success_block( $message ) {
    echo "<div style=\"color: #009900; height: 25px; width: 400px; text-align:center; background-color:transparent; margin: 10px auto; border: 1px dashed #060;\">{$message}</div>";
}

?>