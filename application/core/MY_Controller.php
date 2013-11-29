<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
	function __construct() {
		parent::__construct();
	}

	protected function send_mail($options) {
		$this->load->library( 'email' );

		$this->email->initialize( $config['mail'] );
		
		$this->email->from( $config['mail']['smtp_user'], $config['mail']['sender_name'] );
		$this->email->to( $options['to'] );
		
		$this->email->subject( $options['subject'] );
		$this->email->message( $options['message'] );
		
		return $this->email->send();
	}
}

class JSON_Controller extends CI_Controller {

	var $session_data;
	var $json_data;

	function __construct() {
		parent::__construct();

		$this->json_data = array(
			'status' => 0
		);

		$this->session_data = $this->session->get(TRUE);
	}

	protected function _set($key, $value = NULL) {
		if ($value === NULL && is_array($key)) {
			foreach ($key as $k => $v) {
				$this->_set($k, $v);
			}
		} else {
			$this->json_data[$key] = $value;
		}
	}

	protected function _set_status($status = NULL) {
		if ($status !== NULL) {
			$this->json_data['status'] = $status;
		}
	}

	protected function _set_data($data = NULL) {
		if (is_array($data)) {
			$this->json_data['data'] = $data;
		}
	}

	protected function _set_msg($msg = NULL) {
		if ($msg) {
			$this->json_data['msg'] = $msg;
		}
	}

	protected function _out($status = NULL, $data = NULL, $msg = NULL) {
		$this->_set_status($status);
		$this->_set_data($data);
		$this->_set_msg($msg);
		$this->load->view('json', array('json' => $this->json_data));
	}

	protected function _out_data($data) {
		$this->_out(NULL, $data);
	}

	// 所有验证的总入库，以参数顺序调用其他验证器。
	// 过程中每个验证器在失败的适合设定唯一的status码用于前端判断。
	// e.g.
	// $this->check('session', 'permission', 'input'); // TRUE/FALSE
	protected function _check () {
		$methods = func_get_args();
		foreach ($methods as $method) {
			$m = '_check_' . $method;
			if ($this->$m() === FALSE) {
				$this->_out();
				return FALSE;
			}
		}
		return TRUE;
	}

	protected function _check_input() {
		$result = $this->form_validation->run();
		if ($result === FALSE) {
			$this->_set_status(1);
			$this->_set('fieldError', $this->form_validation->error_array());
		}
		return $result;
	}

	protected function _check_session() {
		if (!$this->session_data) {
			$this->_set_status(2);
			return FALSE;
		}
		return TRUE;
	}

	protected function _check_permission() {
		$this->load->model('Administrator_model', 'account');

		$uri = $this->uri->uri_string();
		
		$permissions = $this->account->get_permission_by_role($this->session_data['roleId']);

		foreach ($permissions as $permission) {
			$path = $permission->path;
			if ($path == $uri) {
				return TRUE;
			}
			if (strpos($path, '*') == strlen($path) - 1) {
				$wildcard = substr($path, 0, -1);
				if (strlen($wildcard) === 0 || strpos($uri, $wildcard) === 0) {
					return TRUE;
				}
			}
		}

		$this->_set_status(3);
		return FALSE;
	}
}