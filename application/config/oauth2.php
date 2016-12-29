<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['oauth2'] = array();

if (file_exists(FCPATH.'env/'.HOST_ENV_NAME.'/oauth2.php')) {
	require_once(FCPATH.'env/'.HOST_ENV_NAME.'/oauth2.php');
}
