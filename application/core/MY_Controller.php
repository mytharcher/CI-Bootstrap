<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
			
class MY_Controller extends CI_Controller {
	
	var $json_data;
	var $checked = FALSE;

	// 用于在表单验证时，忽略规则中“!key”表示的特定值。
	// 目前当数据库表有设置唯一字段时，再update对应字段，
	// 当新值和数据库中该字段值相同时，不认为是重复判定。
	var $special_input_value;

	var $main_model;


	function __construct() {
		parent::__construct();

		$this->config->load(OAUTH_PROVIDER_WECHAT, TRUE);
		$wechatConfig = $this->config->item(OAUTH_PROVIDER_WECHAT);

		$this->json_data = array(
			'status' => 0,
			'meta' => array(
				'host' => $_SERVER['HTTP_HOST'],
				'path' => preg_replace('/^\/+/', '/', uri_string()),
				'wechatAppId' => $wechatConfig['app_id']
			)
		);

		if (!$this->input->is_cli_request()) {
			if (isset($_SESSION['redirect']) && $_SESSION['redirect'] == $_SERVER['REQUEST_URI']) {
				unset($_SESSION['redirect']);
			}

			$device = 'desktop';

			$mobile_detector = new Detection\MobileDetect();
			if ($mobile_detector->isMobile()) {
				if ($mobile_detector->isTablet()) {
					$device = 'tablet';
				} else {
					$device = 'mobile';
				}
			}
			$this->set_meta('device', $device);

			$this->set_meta('wechat', strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false);
		} else {
			$this->load->database();
		}

		if (isset($this->main_model)) {
			$this->load->model($this->main_model, 'model');
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
		if (!isset($value)) {
			if (is_array($key) && (bool)count(array_filter(array_keys($key), 'is_string'))) {
				$this->json_data['data'] = array_merge($this->json_data['data'], $key);
			} else if (isset($key)) {
				$this->json_data['data'] = $key;
			}
		} else if (is_string($key)) {
			$this->json_data['data'][$key] = $value;
		}
	}

	protected function set_meta($key, $value = NULL) {
		// 保证在设置数据时已经建立meta集合
		if ($key !== NULL && !isset($this->json_data['meta'])) {
			$this->json_data['meta'] = array();
		}
		if ($value === NULL && is_array($key)) {
			$this->json_data['meta'] = array_merge($this->json_data['meta'], $key);
		} else if (is_string($key)) {
			$this->json_data['meta'][$key] = $value;
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
	// 0.2: 请根据地址跳转

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
			$this->set_status(STATUS_INPUT_INVALID);
			$this->set_data($this->form_validation->error_array());
		}
		return $result;
	}

	// 验证登录状态。错误码：2
	protected function check_session($only = FALSE) {
		if (!isset($_SESSION) || !isset($_SESSION['user'])) {
			if (!$only) {
				if (!$this->is_ajax() && !isset($_SESSION['redirect'])) {
					$_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
				}
				$this->set_status(STATUS_NEED_LOGIN);
			}
			return FALSE;
		}
		return TRUE;

		// if (isset($_SESSION)) {
		// 	if (isset($_SESSION['user'])) {
		// 		return TRUE;
		// 	}

		// 	if (!$only) {
		// 		if (!$this->is_ajax() && !isset($_SESSION['redirect'])) {
		// 			$_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
		// 		}
		// 	}
		// 	$this->set_status(STATUS_NEED_LOGIN);

		// 	if (isset($_SESSION['oauth'])) {
		// 		$this->set_status(STATUS_NEED_LOGIN);	
		// 	}

		// 	return FALSE;
		// }
	}

	protected function check_session_oauth($only = FALSE) {
		if (!isset($_SESSION) || !isset($_SESSION['oauth'])) {
			if (!$only) {
				$this->set_status(STATUS_OAUTH_NEED_LOGIN);
			}
			return FALSE;
		}

		return TRUE;
	}

	// 验证登录状态。错误码：2
	protected function check_admin($only = FALSE) {
		if (!isset($_SESSION) || !isset($_SESSION['admin'])) {
			if (!$only) {
				if (!$this->is_ajax() && !isset($_SESSION['redirect'])) {
					$_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
				}
				$this->set_status(STATUS_NEED_LOGIN);
			}
			return FALSE;
		}
		return TRUE;
	}

	// 验证操作权限。错误码：3
	protected function check_permission($only = FALSE) {
		$this->load->model('Role_model', 'role');

		$uri = $this->uri->uri_string();
		
		$permissions = $this->role->get_permissions($_SESSION['admin']['role']['id']);

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
			$this->set_status(STATUS_BIZ_ACTION_NOT_ALLOWED);
		}
		return FALSE;
	}

	// 是否首次登录未完善注册信息。true：已完善，false，未完善
	// 错误码：4.1
	// protected function check_register($only = FALSE) {
	// 	$account = $_SESSION['user'];
	// 	if ($account['role']['id'] != 2 && $account['status']['id'] == 1) {
	// 		if (!$only) {
	// 			if (!$this->is_ajax() && !$this->session->get_redirect()) {
	// 				$this->session->set_redirect($this->uri->uri_string);
	// 			}
	// 			$this->set_status(STATUS_ACCOUNT_INFO_NOT_COMPLETED);
	// 		}
	// 		return FALSE;
	// 	}
	// 	return TRUE;
	// }

	protected function is_ajax() {
		$headers = getallheaders(); // For PHP < 5.4 define in server_helper.php;
		return isset($headers['X-Requested-With']) && $headers['X-Requested-With'] == 'XMLHttpRequest';
	}

	protected function render($template, $data = NULL) {
		$this->set_data($data);
		$this->set('session', $_SESSION);
		$this->parser->parse($template, $this->json_data);
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
		return $this->out_message(STATUS_BIZ_CONTENT_NOT_EXIST);
	}

	protected function out_message($status = NULL, $message = NULL) {
		if (isset($status)) {
			$this->set_status($status);
		}

		if (isset($message)) {
			$this->set_msg($message);
		}

		if ($this->is_ajax()) {
			$this->json();
		} else {
			$this->render('targets/message.tpl');
		}
	}

	protected function redirect($url = '') {
		$url = 'http://'.$_SERVER['HTTP_HOST'].'/'.preg_replace('/^\/(.*)/', "$1", $url);
		if ($this->is_ajax()) {
			$this->set('redirect', $url);
			return $this->json();
		}
		return redirect($url);
	}
}



class Entity_Controller extends MY_Controller {

	function __construct() {
		parent::__construct();
	}

	// GET /api/$controller
	public function index() {
		return $this->query();
	}

	// GET /api/$controller/get
	public function get($id) {
		if ($this->check('admin', 'permission')) {	
			$data = $this->model->get_one(array($this->model->id_key => $id));
			$this->set_data($data);
		}

		$this->out();
	}

	// POST /api/$controller/create
	public function create() {
		if ($this->check('admin', 'permission', 'input')) {
			$data = $this->input->post();
			$id = $this->model->create($data);
			
			if ($id) {
				$this->set_data(array($this->model->id_key => $id));
			} else {
				$this->set_status(STATUS_SERVER_EXCEPTION);
			}
		}

		$this->out();
	}

	// POST /api/$controller/update/:id
	public function update($id) {
		$this->special_input_value = $id;
		if ($this->check('admin', 'permission', 'input')) {
			$data = $this->input->post();
			if ($this->model->update($id, $data) === FALSE) {
				$this->set_status(STATUS_SERVER_EXCEPTION);
			}
		}

		$this->out();
	}

	// POST /api/$controller/delete/:id
	public function delete($id) {
		if ($this->check('admin', 'permission')) {
			if ($this->model->delete($id) === FALSE) {
				$this->set_status(STATUS_SERVER_EXCEPTION);
			}
		}

		$this->out();
	}

	protected function remove($query) {
		if ($this->model->remove($query) === FALSE) {
			$this->set_status(STATUS_SERVER_EXCEPTION);
		}

		$this->out();
	}

	protected function _save($id = NULL) {
		$is_create = !$id;

		$input = $this->input->post();
		
		$id = $this->model->save($input, $id);

		if ($id) {
			if ($is_create) {
				$this->set_data(array($this->model->id_key => $id));
			}
		} else {
			$this->set_status(STATUS_SERVER_EXCEPTION);
		}
	}

	public function query() {
		if ($this->check('admin', 'permission')) {
			$input = $this->input->get();
			if (!$input) {
				$input = array();
			}
			$options = array();

			if (isset($input['query'])) {
				$options['query'] = $input['query'];
				log_message('debug', $input['query']);
				unset($input['query']);
			}

			if (isset($input['page']) && isset($input['perPage'])) {
				$options['limit'] = $input['perPage'];
				$options['offset'] = ($input['page'] - 1) * $input['perPage'];

				unset($input['page']);
				unset($input['perPage']);

				$this->set('pager', array(
					'total' => $this->model->count_all($input, $options)
				));
			}

			if (isset($input['sort']) && isset($input['order'])) {
				$options['sort'] = array($input['sort'] => $input['order']);

				unset($input['sort']);
				unset($input['order']);
			}

			$data = $this->model->get_all($input, $options);

			if (isset($data)) {
				$this->set_data($data);
			} else {
				$this->set_status(STATUS_SERVER_EXCEPTION);
			}
		}

		$this->json();
	}
}
