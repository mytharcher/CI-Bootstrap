<?php
class Account_model extends Entity_Model {

	var $main_table = 'Account';

	function __construct () {
		parent::__construct();
	}

	function get_all($query = array(), $options = array()) {
		$options = array_merge(array(
			'join' => array(
				'Role' => array('roleId', 'id'),
				'Status' => array('statusId', 'id')
			)
		), $options);

		return parent::get_all($query, $options);
	}
}
