<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_group = 'default';
$active_record = TRUE;

$db['default']['hostname'] = 'localhost'; // 数据库主机名。若同主机则可以用localhost
$db['default']['username'] = 'username'; // 数据库用户名
$db['default']['password'] = ''; // 数据库密码
$db['default']['database'] = 'database'; // 数据库

// 更多额外设置请参照CI主页
$db['default']['dbdriver'] = 'mysql'; // 数据库驱动。不要修改
$db['default']['dbprefix'] = ''; // 数据库表前缀。一般不要修改
$db['default']['pconnect'] = TRUE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;
