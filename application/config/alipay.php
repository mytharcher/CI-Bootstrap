<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CI Alipay
 *
 * 支付宝集成配置
 * 
 */

$config['payment_type'] = 1;
$config['transport'] = 'http';
$config['input_charset'] = 'utf-8';
$config['sign_type'] = 'MD5';
$config['notify_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/order/callback/notify';
$config['return_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/order/callback/return';
$config['cacert'] = APPPATH.'third_party/alipay/cacert.pem';


require_once(WEBROOT.'/env/'.$_SERVER['HTTP_HOST'].'/alipay.php');
