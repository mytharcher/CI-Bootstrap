<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends MY_Controller {

	function __construct() {
		parent::__construct();
	}

	public function index() {
		// $this->load->view('index');
		// Load the template from the views directory
		if ($this->check('session', 'permission')) {
			$this->load->model('Role_model', 'role');
			$data = array(
				'permissions' => $this->role->get_permissions($this->session_data['role']['id'])
			);
			$this->render('targets/admin.tpl', $data);
		} else {
			$this->redirect('/account/login');
		}
	}
}
