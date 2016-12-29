<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (file_exists(FCPATH.'env/'.HOST_ENV_NAME.'/email.php')) {
	require_once(FCPATH.'env/'.HOST_ENV_NAME.'/email.php');
}
