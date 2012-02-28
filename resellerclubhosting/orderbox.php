<?php


	define('ORDERBOX_HTTP_API_BASEURL', 'https://httpapi.com/api/');


	function orderbox_api_client($userid, $password)
	{
		$auth = array('auth-userid'=>$userid, 'auth-password'=>$password);

		return function ($method, $path, $params=array(), &$response_headers=array()) use ($auth)
		{
			$url = ORDERBOX_HTTP_API_BASEURL.ltrim($path, '/');

			$query_params = empty($params) ? $auth : array_merge($params, $auth);

			$query = orderbox_http_build_query($query_params);

			if ('POST' == $method) $payload = empty($params) ? '' : orderbox_http_build_query($params);
			else $payload = '';

			$request_headers = ('POST' == $method) ? array("Content-Type: application/x-www-form-urlencoded; charset=utf-8") : array();

			$response = curl_http_api_request_($method, $url, $query, $payload, $request_headers, $response_headers);
			$response = json_decode($response, true);

			if (isset($response['error']) or ($response_headers['http_status_code'] >= 400))
				throw new OrderboxApiException(compact('method', 'path', 'params', 'response_headers', 'response', 'auth'));

			return $response;
		};
	}

		function curl_http_api_request_($method, $url, $query='', $payload='', $request_headers=array(), &$response_headers=array())
		{
			$url = curl_append_query_($url, $query);
			$ch = curl_init($url);
			curl_setopts_($ch, $method, $payload, $request_headers);
			$response = curl_exec($ch);
			$errno = curl_errno($ch);
			$error = curl_error($ch);
			curl_close($ch);

			if ($errno) throw new OrderboxCurlException($error, $errno);

			list($message_headers, $message_body) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
			$response_headers = curl_parse_headers_($message_headers);

			return $message_body;
		}

			function curl_append_query_($url, $query)
			{
				if (empty($query)) return $url;
				if (is_array($query)) return "$url?".http_build_query($query);
				else return "$url?$query";
			}

			function curl_setopts_($ch, $method, $payload, $request_headers)
			{
				curl_setopt($ch, CURLOPT_HEADER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_USERAGENT, 'HAC');
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);

				if ('GET' == $method)
				{
					curl_setopt($ch, CURLOPT_HTTPGET, true);
				}
				else
				{
					curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, $method);
					if (!empty($request_headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
					if (!empty($payload))
					{
						if (is_array($payload)) $payload = http_build_query($payload);
						curl_setopt ($ch, CURLOPT_POSTFIELDS, $payload);
					}
				}
			}

			function curl_parse_headers_($message_headers)
			{
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

	// hack to overcome java style query array params: http://manage.resellerclub.com/kb/answer/755#array
	function orderbox_http_build_query($params)
	{
		$params_str = array();

		foreach ($params as $key => $value)
		{
			$key = rawurlencode($key);
			if (is_array($value)) $params_str[] = "$key=".implode("&$key=", $value);
			else $params_str[] = "$key=".urlencode($value);
		}

		return implode('&', $params_str);
	}

	class OrderboxCurlException extends Exception { }
	class OrderboxApiException extends Exception
	{
		protected $info;

		function __construct($info)
		{
			$this->info = $info;
			parent::__construct($info['response_headers']['http_status_message'], $info['response_headers']['http_status_code']);
		}

		function getInfo() { $this->info; }
	}

?>