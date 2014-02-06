<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$CI = &get_instance();

$all_rules = array(
	// 'id' => array(
	// 	'field' => 'id',
	// 	'label' => 'ID',
	// 	'rules' => 'trim|required'
	// ),
	// 'email' => array(
	// 	'field' => 'email',
	// 	'label' => '邮箱账号',
	// 	'rules' => 'trim|required|min_length[5]|max_length[128]|valid_email|xss_clean'
	// ),
	// 'email_unique' => array(
	// 	'field' => 'email',
	// 	'label' => '邮箱账号',
	// 	'rules' => 'trim|required|min_length[5]|max_length[128]|valid_email|is_unique[administrator.email!id]|xss_clean'
	// ),
	// 'password' => array(
	// 	'field' => 'password',
	// 	'label' => '密码',
	// 	'rules' => 'trim|required|min_length[6]|max_length[12]|sha1'
	// ),
	// 'password_repeat' => array(
	// 	'field' => 'repeatpassword',
	// 	'label' => '重复密码',
	// 	'rules' => 'trim|required|min_length[6]|max_length[12]|matches[newpassword]|sha1'
	// ),
	// 'username' => array(
	// 	'field' => 'name',
	// 	'label' => '用户名称',
	// 	'rules' => 'trim|required|max_length[16]'
	// ),
	// 'username_unique' => array(
	// 	'field' => 'name',
	// 	'label' => '用户名称',
	// 	'rules' => 'trim|required|max_length[16]|is_unique[administrator.name!id]|xss_clean'
	// ),
	// 'roleId' => array(
	// 	'field' => 'roleId',
	// 	'label' => '角色',
	// 	'rules' => 'trim|required'
	// ),
);

$groups = array(
	// 'account' => array(
	// 	&$all_rules['email_unique'],
	// 	&$all_rules['username_unique'],
	// 	&$all_rules['roleId']
	// ),
);

$config = array(
	// 'api/account/login' => array(
	// 	&$all_rules['email'],
	// 	&$all_rules['password']
	// ),
	// 'api/account/create' => &$groups['account'],
	// 'api/account/update/'.$CI->uri->segment(4) => &$groups['account'],
	// 'api/account/change' => array(
	// 	&$all_rules['email_unique'],
	// 	&$all_rules['username_unique']
	// ),
	// 'api/account/changepassword' => array(
	// 	&$all_rules['password'],
	// 	&$all_rules['password_new'],
	// 	&$all_rules['password_repeat']
	// ),
);