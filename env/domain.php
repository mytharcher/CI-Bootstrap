<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['app_domain'] = 'localhost';

if (!isset($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = $config['app_domain'];
}
