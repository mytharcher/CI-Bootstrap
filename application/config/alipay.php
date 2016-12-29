<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CI Alipay
 *
 * 支付宝集成配置
 * 
 */

$config['notify_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/order/callback_alipay/notify';
$config['return_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/order/callback_alipay/return';

if (file_exists(FCPATH.'env/'.HOST_ENV_NAME.'/alipay.php')) {
	require_once(FCPATH.'env/'.HOST_ENV_NAME.'/alipay.php');
}
