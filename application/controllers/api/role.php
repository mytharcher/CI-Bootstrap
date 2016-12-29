<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Role extends Entity_Controller {
	var $main_model = 'Role_model';
	var $relation_keys = array('operation');

	function __construct() {
		parent::__construct();
	}

	public function query() {
		if ($this->check('admin', 'permission')) {
			$data = $this->model->get_all(array(
				'fetch' => array('Operation' => 1)
			));
			$this->set_data($data);
		}

		$this->json();
	}

	public function operations() {
		if ($this->check('admin', 'permission')) {
			$data = $this->model->get_operations();
			$this->set_data($data);
		}

		$this->json();
	}

	public function create() {
		if ($this->check('admin', 'permission', 'input')) {
			$this->_save();
		}
		$this->json();
	}

	public function update($id) {
		if ($this->check('admin', 'permission', 'input')) {
			$this->_save($id);
		}
		$this->json();
	}
}
