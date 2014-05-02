<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['charset'] = 'utf-8';
$config['wordwrap'] = FALSE;
$config['sender_name'] = '['.$_SERVER['HTTP_HOST'].']';

/**
 * 163邮箱SMTP服务配置方式
 */
$config['protocol'] = 'smtp';
$config['smtp_host'] = 'smtp.163.com'; // SMTP邮件服务器，用于创建用户发邮件通知的服务。建议使用163/Gmail等服务
$config['smtp_port'] = 25;
$config['smtp_user'] = 'username'; // SMTP邮件服务器的用户名
$config['smtp_pass'] = ''; // 邮箱密码
$config['smtp_account'] = 'username@163.com'; // 邮箱地址
$config['smtp_timeout'] = 5;
$config['mailtype'] = 'text';
$config['newline'] = "\r\n";

/**
 * Gmail邮箱SMTP服务配置方式
 */
// $config['protocol'] = 'smtp';
// $config['smtp_crypto'] = 'ssl';
// $config['smtp_host'] = 'smtp.gmail.com';
// $config['smtp_port'] = 465;
// $config['smtp_user'] = 'username@gmail.com';
// $config['smtp_pass'] = '';
// $config['smtp_account'] = 'username@gmail.com';
// $config['smtp_timeout'] = 5;
// $config['mailtype'] = 'text';
// $config['newline'] = "\r\n"; // 必须加这个设置，否则发不出邮件
