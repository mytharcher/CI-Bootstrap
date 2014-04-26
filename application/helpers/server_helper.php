<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * For PHP version < 5.4 no function getallheaders()
 */

if (! function_exists('getallheaders')) {
	function getallheaders() {
		$headers = array();
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) <> 'HTTP_') {
				continue;
			}
			$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			$headers[$header] = $value;
		}
		return $headers;
	}
}
