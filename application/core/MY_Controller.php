<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
	
	var $json_data;
	var $checked = FALSE;
	// 用于在表单验证时，忽略规则中“!key”表示的特定值。
	// 目前当数据库表有设置唯一字段时，再update对应字段，
	// 当新值和数据库中该字段值相同时，不认为是重复判定。
	var $special_input_value;



	function __construct() {
		parent::__construct();

		$this->json_data = array(
			'status' => 0,
			'data' => array()
		);

		$this->session_data = $this->session->all_userdata();
		if ($this->session->get_redirect() == $this->uri->uri_string) {
			$this->session->set_redirect();
		}
	}

	protected function set($key, $value = NULL) {
		if ($value === NULL && is_array($key)) {
			foreach ($key as $k => $v) {
				$this->set($k, $v);
			}
		} else {
			$this->json_data[$key] = $value;
		}
	}

	protected function set_status($status = NULL) {
		if ($status !== NULL) {
			$this->json_data['status'] = $status;
		}
	}

	protected function set_data($key, $value = NULL) {
		if ($value === NULL && is_array($key)) {
			$this->json_data['data'] = array_merge($this->json_data['data'], $key);
		} else if (is_string($key)) {
			$this->json_data['data'][$key] = $value;
		}
	}

	protected function set_msg($msg = NULL) {
		if ($msg) {
			$this->json_data['msg'] = $msg;
		}
	}

	// 所有验证的总入库，以参数顺序调用其他验证器。
	// 过程中每个验证器在失败的适合设定唯一的status码用于前端判断。
	// e.g.
	// $this->check('session', 'permission', 'input'); // TRUE/FALSE
	//
	// 错误码
	// 1: 输入数据验证失败
	// 2: 未登录
	// 3: 没有操作权限
	// 4: 返回数据业务错误
	// 4.4: 请求内容不存在
	// 5: 服务器或数据库其他错误，未捕获的异常等
	// 6: 邮件发送失败
	protected function check () {
		$this->checked = TRUE;
		$methods = func_get_args();
		foreach ($methods as $method) {
			$m = 'check_' . $method;
			if ($this->$m() === FALSE) {
				return FALSE;
			}
		}
		return TRUE;
	}

	// 验证输入。错误码：1
	protected function check_input() {
		$result = $this->form_validation->run();
		if ($result === FALSE) {
			$this->set_status(1);
			$this->set('fieldError', $this->form_validation->error_array());
		}
		return $result;
	}

	// 验证登录状态。错误码：2
	protected function check_session() {
		if (!$this->session_data || !isset($this->session_data['id'])) {
			if (!$this->session->get_redirect()) {
				$this->session->set_redirect($this->uri->uri_string);
			}
			$this->set_status(2);
			return FALSE;
		}
		return TRUE;
	}

	// 验证操作权限。错误码：3
	protected function check_permission() {
		$this->load->model('Role_model', 'role');

		$uri = $this->uri->uri_string();
		
		$permissions = $this->role->get_permissions($this->session_data['role']['id']);

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

		$this->set_status(3);
		return FALSE;
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

	protected function render($template, $data = NULL) {
		$this->set_data($data);
		$this->set_data('session', $this->session_data);
		$this->parser->parse($template, $this->json_data['data']);
	}

	protected function json($data = NULL) {
		$this->set_data($data);
		$this->load->view('json', array('json' => $this->json_data));
	}

	protected function out($data = NULL, $tpl = '') {
		if (!$tpl && is_string($data)) {
			$tpl = $data;
			$data = NULL;
		}
		$this->set_data($data);
		$headers = getallheaders(); // For PHP < 5.4 define in server_helper.php;

		if (isset($headers['X-Requested-With']) && $headers['X-Requested-With'] == 'XMLHttpRequest') {
			$this->json();
		} else {
			$this->render($tpl ? $tpl : 'targets/'.uri_string().'.tpl');
		}
	}

	protected function redirect($url = '') {
		return redirect('http://'.$_SERVER['HTTP_HOST'].'/'.$url);
	}

	protected function send_mail($to, $template, $data) {
		$this->config->item('email', TRUE);
		$config = $this->config->item('email');
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



class Entity_Controller extends MY_Controller {
	var $main_model;
	var $relation_keys = array();

	function __construct() {

		parent::__construct();

		$this->load->model($this->main_model, 'model');
	}

	// GET /api/$controller
	public function index() {
		if ($this->check('session', 'permission')) {
			$data = $this->model->get_all();
			$this->set_data($data);
		}

		$this->out();
	}

	// GET /api/$controller/get
	public function get($id) {
		if ($this->check('session', 'permission')) {	
			$data = $this->model->get_one(array('id' => $id));
			$this->set_data($data);
		}

		$this->out();
	}

	// POST /api/$controller/create
	public function create() {
		if ($this->check('session', 'permission', 'input')) {
			$data = $this->input->post();
			$id = $this->model->create($data);
			if ($id) {
				$this->set_data(array('id' => $id));
			} else {
				$this->set_status(1);
			}
		}

		$this->out();
	}

	// POST /api/$controller/update/:id
	public function update($id) {
		$this->special_input_value = $id;
		if ($this->check('session', 'permission', 'input')) {
			$data = $this->input->post();
			if ($this->model->update($id, $data) === FALSE) {
				$this->set_status(1);
			}
		}

		$this->out();
	}

	// POST /api/$controller/delete/:id?
	public function delete($id) {
		if ($this->check('session', 'permission')) {
			if ($this->model->delete($id) === FALSE) {
				$this->set_status(5);
			}
		}

		$this->out();
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
			$this->set_data(array('id' => $id));
		} else {
			$this->model->update($id, $input);
		}
		if ($id) {
			foreach ($relations as $model => $relation) {
				$this->update_relation($id, $model, $relation);
			}
		} else {
			$this->set_status(1);
		}
	}
}
