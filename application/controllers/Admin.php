<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends Entity_Controller {

	var $main_model = 'Administrator_model';

	public function index() {
		// $this->load->view('index');
		// Load the template from the views directory
		if ($this->check('admin', 'permission')) {
			$this->load->model('Role_model', 'role');
			$data = array(
				'permissions' => $this->role->get_permissions($_SESSION['admin']['role']['id'])
			);
			$this->render('targets/admin/main.tpl', $data);
		} else {
			$this->render('targets/admin/login.tpl');
		}
	}
	
	public function get($id = NULL) {
		$data = NULL;

		if ($this->check('admin')) {
			if ($id) {
				if ($this->check('permission')) {
					$data = $this->model->get($id);
				}
			} else {
				$data = $this->model->get($_SESSION['admin']['id']);
			}
		}

		$this->json($data);
	}

	public function login() {
		if ($this->check('input')) {
			$email = $this->input->post('email');
			$password = $this->input->post('password');

			$account = $this->model->get_one(array(
				'email' => $email,
				'password' => sha1($password)
			));

			if ($account) {
				unset($account['password']);

				$_SESSION['admin'] = $account;
			} else {
				$this->set_status(STATUS_BIZ_ACTION_NOT_ALLOWED);
			}
		}

		$this->json();
	}

	public function logout() {
		session_destroy();

		$this->out();
	}
}
