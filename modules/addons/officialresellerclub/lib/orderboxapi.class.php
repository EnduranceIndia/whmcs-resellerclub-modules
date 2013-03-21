<?php

define('ORDERBOX_HTTP_API_BASEURL', 'https://httpapi.com/api/');

class orderboxapi {

    private $api_baseurl = 'https://httpapi.com/api/';
    private $auth_resellerid = '';
    private $api_key = '';
    private $auth_params = array();
	private $log_flag = true;

    public function __construct($resellerid, $api_key,$enable_log = true, $profiler = 'rc-whmcs', $url = '') {
        $this->api_baseurl = empty($url) == false ? $url : $this->api_baseurl;
        $this->auth_resellerid = $resellerid;
        $this->api_key = $api_key;
        $this->auth_params = array('auth-userid' => $resellerid, 'api-key' => $api_key, 'profiler-ink' => $profiler);
        $this->log_flag = $enable_log;


    }

    /**
     * Makes and API call to orderbox and Logs in WHMCS module if $module_name and $action_name are specified
     *
     * @param type $method http method to make api request
     * @param type $path api path request
     * @param type $params array of parameters for api request
     * @param type $response_headers raw response of the api call
     * @param type $module_name moduel name to be logged
     * @param type $action_name moduel action to be logged
     * @return type
     */
    public function api($method, $path, $params = array(), &$response_headers = array(), $module_name = '', $action_name = '') {

        $url = $this->api_baseurl . ltrim($path, '/');
        $query_params = empty($params) ? $this->auth_params : array_merge($params, $this->auth_params);
        $query = $this->orderbox_http_build_query($query_params);
        if ('POST' == $method) {
            $get_fields = '';
            $post_fields = empty($query_params) ? '' : $this->orderbox_http_build_query($query_params);
            $request_headers = array("Content-Type: application/x-www-form-urlencoded; charset=utf-8");
        } else {
            $get_fields = $query;
            $post_fields = '';
            $request_headers = array();
        }

        try {
            $response_json = $this->http_api_request($method, $url, $get_fields, $post_fields, $request_headers, $response_headers);
        } catch (Exception $e) {
            return "Exception oboxapi : " . $e->getMessage();
        }

        $response = json_decode($response_json, true);

        if ($this->log_flag && function_exists('logModuleCall') && (!empty($module_name) || !empty($action_name))) {
            logModuleCall($module_name, $action_name, array_merge(array('api' => $path), $params), $response_headers, $response);
        }

        return $response;
    }

    private function orderbox_http_build_query($params) {

        $params_str = array();
        foreach ($params as $key => $value)
        {
            $key = rawurlencode($key);
            if (is_array($value)) {
                $params_str[] = "$key=" . implode("&$key=", $value);
            } else {
                $params_str[] = "$key=" . urlencode($value);
            }
        }
        return implode('&', $params_str);
            
    }

    private function http_api_request($method, $url, $get_fields = '', $post_fields = '', $request_headers = array(), &$response_headers = array()) {

        $ch = curl_init();
        $this->set_options($ch, $method, $url, $get_fields, $post_fields);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new Exception($error, $errno);
        }

        list( $response_headers_txt, $response_body ) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
        $response_headers = $this->parse_headers($response_headers_txt);

        return $response_body;
            
    }

    private function set_options($handle, $method, $url, $get_fields, $post_fields) {

        curl_setopt($handle, CURLOPT_VERBOSE, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_MAXREDIRS, 3);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);

        if ('GET' == $method) {
            curl_setopt($handle, CURLOPT_URL, $this->append_query($url, $get_fields));
            curl_setopt($handle, CURLOPT_HTTPGET, true);
        } else if ('POST' == $method) {
            curl_setopt($handle, CURLOPT_URL, $url);
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $post_fields);
        } else {
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
        }
            
    }

    private function parse_headers($message_headers) {

        $header_lines = preg_split("/\r\n|\n|\r/", $message_headers);
        $headers = array();
        list(, $headers['http_status_code'], $headers['http_status_message']) = explode(' ', trim(array_shift($header_lines)), 3);
        foreach ($header_lines as $header_line) 
		{
            list($name, $value) = explode(':', $header_line, 2);
            $name = strtolower($name);
            $headers[$name] = trim($value);
        }

        return $headers;
            
    }

    private function append_query($url, $query) {

        if (empty($query))
            return $url;
        if (is_array($query))
            return "$url?" . http_build_query($query);
        else
            return "$url?$query";
    }

}

?>