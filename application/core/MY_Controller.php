<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
	// var $models;

	function __construct() {
		parent::__construct();

		// $this->models = array();
	}

	protected function send_mail($to, $template, $data) {
		$config = $this->config->item('mail');
		$this->load->library('email', $config);
		// var_dump($config);
		
		$this->email->from( $config['smtp_account'], $config['sender_name'] );
		$this->email->to( $to );
		$subject = $this->parser->parse($template.'.title.tpl', $data, TRUE);
		$content = $this->parser->parse($template.'.tpl', $data, TRUE);
		$this->email->subject( $subject );
		$this->email->message( $content );
		
		return $this->email->send();
	}
}



class Page_Controller extends MY_Controller {
	var $main_model;

	function __construct() {
		parent::__construct();
	}

	protected function read_relation($id, $model, $full = FALSE) {
		$this->load->model($model);
		return $this->$model->foreign($id, $full);
	}

	protected function _out($template, $data = NULL) {
		$this->parser->parse($template, $data);
	}
}



class JSON_Controller extends MY_Controller {

	var $session_data;
	var $json_data;
	var $checked = FALSE;
	// 用于在表单验证时，忽略规则中“!key”表示的特定值。
	// 目前当数据库表有设置唯一字段时，再update对应字段，
	// 当新值和数据库中该字段值相同时，不认为是重复判定。
	var $special_input_value;

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
		$this->checked = TRUE;
		$methods = func_get_args();
		foreach ($methods as $method) {
			$m = '_check_' . $method;
			if ($this->$m() === FALSE) {
				return FALSE;
			}
		}
		return TRUE;
	}

	// 验证输入。错误码：1
	protected function _check_input() {
		$result = $this->form_validation->run();
		if ($result === FALSE) {
			$this->_set_status(1);
			$this->_set('fieldError', $this->form_validation->error_array());
		}
		return $result;
	}

	// 验证登录状态。错误码：2
	protected function _check_session() {
		if (!$this->session_data) {
			$this->_set_status(2);
			return FALSE;
		}
		return TRUE;
	}

	// 验证操作权限。错误码：3
	protected function _check_permission() {
		$this->load->model('Role_model', 'role');

		$uri = $this->uri->uri_string();
		
		$permissions = $this->role->get_permissions($this->session_data['roleId']);

		foreach ($permissions as $path => $exist) {
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

	// 1.1：登录账户或密码错误
	// 错误码：4，返回数据业务错误
	// 错误码：5，服务器或数据库其他错误，未捕获的异常等
	// 错误码：6，邮件发送失败
}



class Entity_Controller extends JSON_Controller {
	var $main_model;
	var $relation_keys = array();

	function __construct() {

		parent::__construct();

		$this->load->model($this->main_model, 'model');
	}

	// GET /api/$controller
	public function index() {
		if ($this->_check('session', 'permission')) {
			$data = $this->model->get_all();
			$this->_set_data($data);
		}

		$this->_out();
	}

	// GET /api/$controller/get
	public function get($id) {
		if ($this->_check('session', 'permission')) {	
			$data = $this->model->get_one(array('id' => $id));
			$this->_set_data($data);
		}

		$this->_out();
	}

	// POST /api/$controller/create
	public function create() {
		if ($this->_check('session', 'permission', 'input')) {
			$data = $this->input->post();
			$id = $this->model->create($data);
			if ($id) {
				$this->_set_data(array('id' => $id));
			} else {
				$this->_set_status(1);
			}
		}

		$this->_out();
	}

	// POST /api/$controller/update/:id
	public function update($id) {
		$this->special_input_value = $id;
		if ($this->_check('session', 'permission', 'input')) {
			$data = $this->input->post();
			if ($this->model->update($id, $data) === FALSE) {
				$this->_set_status(1);
			}
		}

		$this->_out();
	}

	// POST /api/$controller/delete/:id?
	public function delete($id) {
		if ($this->_check('session', 'permission')) {
			if ($this->model->delete($id) === FALSE) {
				$this->_set_status(5);
			}
		}

		$this->_out();
	}

	protected function _save($id = NULL) {
		$input = $this->input->post();
		$relations = array();

		foreach ($this->relation_keys as $item) {
			$relations[ucfirst($item) . '_model'] = $this->input->post($item . 'Id');
			unset($input[$item . 'Id']);
		}

		foreach ($input as $key => $value) {
			if ($value == '') {
				$input[$key] = NULL;
			}
		}

		if (!$id) {
			$id = $this->model->create($input);
			$this->_set_data(array('id' => $id));
		} else {
			$this->model->update($id, $input);
		}
		if ($id) {
			foreach ($relations as $model => $relation) {
				$this->update_relation($id, $model, $relation);
			}
		} else {
			$this->_set_status(1);
		}
	}

	protected function read_relation($id, $model, $full = FALSE) {
		$this->load->model($model);
		return $this->$model->foreign($id, $full);
	}

	protected function read_relation_index($id, $model, $single = FALSE) {
		$mapper = function ($item) {
			return $item['index'];
		};
		$result = $this->read_relation($id, $model);
		return $single ? $result[0] ? $result[0]['index'] : NULL : array_map($mapper, $result);
	}

	protected function write_relation($id, $model, $relation) {
		$success = TRUE;
		if ($relation) {
			$this->load->model($model);
			if (!is_array($relation)) {
				$relation = array($relation);
			}
	
			foreach ($relation as $foreignId) {
				$success = $this->$model->link(array(
					'foreign' => $id,
					'self' => $foreignId)) && $success;
			}
		} else {
			$success = FALSE;
		}
		return $success;
	}

	protected function update_relation($id, $model, $relation) {
		$success = TRUE;
		if ($relation) {
			$this->load->model($model);

			$this->$model->unlink(array('foreign' => $id));

			$success = $this->write_relation($id, $model, $relation);
		} else {
			$success = FALSE;
		}
		return $success;
	}

	protected function delete_relation($id, $model) {
		$this->load->model($model);
		return $this->$model->unlink(array('foreign' => $id));
	}
}
