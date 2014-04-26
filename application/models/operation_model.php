<?php
class Operation_model extends Entity_Model {

	var $main_table     = 'Operation';
	var $relation_table = 'Permission';
	var $foreign_key    = 'roleId';

	function __construct () {
		parent::__construct();
	}
}
