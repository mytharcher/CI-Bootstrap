<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 微信集成配置
 */

$config['payment']['notify_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/order/callback_wechat/notify';
$config['payment']['cert_path'] = FCPATH.'env'.DIRECTORY_SEPARATOR.HOST_ENV_NAME.'wechat'.DIRECTORY_SEPARATOR.'cert'.DIRECTORY_SEPARATOR.'apiclient_cert.pem';
$config['payment']['key_path'] = FCPATH.'env'.DIRECTORY_SEPARATOR.HOST_ENV_NAME.'wechat'.DIRECTORY_SEPARATOR.'cert'.DIRECTORY_SEPARATOR.'apiclient_key.pem';

if (file_exists(FCPATH.'env'.DIRECTORY_SEPARATOR.HOST_ENV_NAME.DIRECTORY_SEPARATOR.'wechat.php')) {
	require_once(FCPATH.'env'.DIRECTORY_SEPARATOR.HOST_ENV_NAME.DIRECTORY_SEPARATOR.'wechat.php');
}
