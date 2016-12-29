<?php
class Administrator_model extends Entity_Model {
	var $main_table = 'Administrator';

	function __construct () {
		parent::__construct();
	}

	function get_all($query = array(), $options = array()) {
		$options = array_merge(array(
			'join' => array(
				'Role' => array('roleId', 'id')
			)
		), $options);

		return parent::get_all($query, $options);
	}
}