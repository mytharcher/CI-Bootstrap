<?php
class Role_model extends Entity_Model {

	var $main_table     = 'Role';
	var $relation_table = 'Permission';
	var $foreign_key    = 'operationId';

	function __construct () {
		parent::__construct();
	}

	function get_name($id) {
		$result = $this->db->get_where('role', array(
			'id' => $id
		));
		return $result->row()->name;
	}

	function get_permissions($id) {
		$permissions = $this->db->select('path', FALSE)
			->from('Role, Operation, Permission')
			->where('Role.id = Permission.roleId AND Permission.operationId = Operation.id')
			->where(array('Role.id' => $id))
			->order_by('path', 'asc')
			->get();

		$result = array();
		foreach ($permissions->result() as $item) {
			$result[$item->path] = 1;
		}
		return $result;
	}

	function get_operations() {
		return $this->db->get('Operation')->result_array();
	}
}
