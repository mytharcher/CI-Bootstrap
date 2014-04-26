<?php
class Status_model extends Entity_Model {

	var $main_table     = 'Status';

	function __construct () {
		parent::__construct();
	}

	function get_name($id) {
		$result = $this->db->get_where($this->main_table, array(
			'id' => $id
		));
		return $result->row()->name;
	}
}