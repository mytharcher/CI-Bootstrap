<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
	function __construct() {
		parent::__construct();
	}
}

class JSON_Controller extends CI_Controller {

	var $json_data;

	function __construct() {
		parent::__construct();

		$this->json_data = array(
			'status' => 0
		);
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

	protected function _out($status = NULL, $data = NULL, $msg = NULL) {
		if ($status !== NULL) {
			$this->json_data['status'] = $status;
		}
		if (is_array($data)) {
			$this->json_data['data'] = $data;
		}
		if ($msg) {
			$this->json_data['msg'] = $msg;
		}
		$this->load->view('json', array('json' => $this->json_data));
	}

}