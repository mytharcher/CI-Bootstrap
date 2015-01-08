<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
	
	var $json_data;
	var $checked = FALSE;
	var $session_data = array();
	// 用于在表单验证时，忽略规则中“!key”表示的特定值。
	// 目前当数据库表有设置唯一字段时，再update对应字段，
	// 当新值和数据库中该字段值相同时，不认为是重复判定。
	var $special_input_value;



	function __construct() {
		parent::__construct();

		$this->json_data = array(
			'status' => 0
		);

		if (!$this->input->is_cli_request()) {
			$this->load->library('session');
			$this->get_session();
			if ($this->session->get_redirect() == $this->uri->uri_string) {
				$this->session->set_redirect();
			}
		} else {
			$this->load->database();
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
		// 保证在设置数据时已经建立data集合
		if ($key !== NULL && !isset($this->json_data['data'])) {
			$this->json_data['data'] = array();
		}
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

	protected function get_session() {
		$this->session_data = $this->session->all_userdata();
		return $this->session_data;
	}

	protected function set_session($data) {
		$this->session->set_userdata($data);
		$this->session_data = $data;
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
	// 4.1: 未完善注册资料
	// 4.3: 请求的操作不允许
	// 4.4: 请求内容不存在
	// 4.8: 请求超时（订单支付超时）
	// 5: 服务器或数据库其他错误，未捕获的异常等
	// 6: 邮件发送失败

	// protected function check () {
	// 	$this->checked = TRUE;
	// 	$methods = func_get_args();
	// 	foreach ($methods as $method) {
	// 		$m = 'check_' . $method;
	// 		if (!function_exists($m)) {
	// 			$this->load->helper("filters/$m");
	// 		}

	// 		if ($m() === FALSE) {
	// 			return FALSE;
	// 		}
	// 	}
	// 	return TRUE;
	// }

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
	protected function check_input($only = FALSE) {
		$result = $this->form_validation->run();
		if ($result === FALSE && !$only){
			$this->set_status(1);
			$this->set_data($this->form_validation->error_array());
		}
		return $result;
	}

	// 验证登录状态。错误码：2
	protected function check_session($only = FALSE) {
		if (!$this->session_data || !isset($this->session_data['id'])) {
			if (!$only) {
				if (!$this->is_ajax() && !$this->session->get_redirect()) {
					$this->session->set_redirect($this->uri->uri_string);
				}
				$this->set_status(2);
			}
			return FALSE;
		}
		return TRUE;
	}

	// 验证操作权限。错误码：3
	protected function check_permission($only = FALSE) {
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
		if (!$only){
			$this->set_status(3);
		}
		return FALSE;
	}

	// 是否首次登录未完善注册信息。true：已完善，false，未完善
	// 错误码：4.1
	protected function check_register($only = FALSE) {
		$account = $this->session_data;
		if ($account['role']['id'] != 2 && $account['status']['id'] == 1) {
			if (!$only) {
				if (!$this->is_ajax() && !$this->session->get_redirect()) {
					$this->session->set_redirect($this->uri->uri_string);
				}
				$this->set_status(4.1);
			}
			return FALSE;
		}
		return TRUE;
	}

	protected function load_meta_data() {
		$metas = func_get_args();
		foreach ($metas as $key) {
			$model = $key.'_model';
			$this->load->model($model);
			$data = $this->$model->get_all();
			$this->set_data($key.'s', $data);
		}
	}

	protected function is_ajax() {
		$headers = getallheaders(); // For PHP < 5.4 define in server_helper.php;
		return isset($headers['X-Requested-With']) && $headers['X-Requested-With'] == 'XMLHttpRequest';
	}

	protected function render($template, $data = NULL) {
		$this->set_data($data);
		$this->set('session', $this->session_data);
		$this->parser->parse($template, $this->json_data);
	}

	protected function json($data = NULL) {
		$this->set_data($data);
		$this->load->view('json', array('json' => $this->json_data));
	}

	protected function status($status, $data = NULL) {
		$this->set_status($status);
		$this->json($data);
	}

	protected function out($data = NULL, $tpl = '') {
		if (!$tpl && is_string($data)) {
			$tpl = $data;
			$data = NULL;
		}
		$this->set_data($data);

		if ($this->is_ajax()) {
			$this->json();
		} else {
			$this->render($tpl ? $tpl : 'targets/'.uri_string().'.tpl');
		}
	}

	protected function relogin() {
		return $this->redirect('account/login');
	}

	protected function out_not_found() {
		if ($this->is_ajax()) {
			$this->set_status(4.4);
			$this->json();
		} else {
			show_404();
		}
	}

	protected function out_message($status, $message = NULL) {
		$this->set_status($status);
		$this->set_msg($message);

		if ($this->is_ajax()) {
			$this->json();
		} else {
			$this->render('targets/message.tpl');
		}
	}

	protected function redirect($url = '') {
		$url = preg_replace('/^\/(.*)/', "$1", $url);
		return redirect('http://'.$_SERVER['HTTP_HOST'].'/'.$url);
	}

	protected function send_mail($to, $template, $data) {
		$this->config->load('email', TRUE);
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

	function __construct() {

		parent::__construct();

		if (isset($this->main_model)) {
			$this->load->model($this->main_model, 'model');
		}
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

	// POST /api/$controller/delete/:id
	public function delete($id) {
		if ($this->check('session', 'permission')) {
			if ($this->model->delete($id) === FALSE) {
				$this->set_status(5);
			}
		}

		$this->out();
	}

	protected function _save($id = NULL) {
		$is_create = !$id;

		$input = $this->input->post();
		
		$id = $this->model->save($input, $id);

		if ($id) {
			if ($is_create) {
				$this->set_data(array('id' => $id));
			}
		} else {
			$this->set_status(5);
		}
	}
}
