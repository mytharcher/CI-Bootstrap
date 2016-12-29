<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Account extends Entity_Controller {
	var $main_model = 'Account_model';


	// 获取用户信息
	public function get($id = NULL) {
		$data = NULL;

		if ($this->check('session')) {
			$data = $this->model->get($_SESSION['user']['id']);
		} else if ($id) {
			if ($this->check('admin', 'permission')) {
				$data = $this->model->get($id);
			}
		} else {
			$this->set_status(STATUS_INPUT_INVALID);
		}

		$this->json($data);
	}

	public function bind() {
		$this->lang->load('oauth');

		if (!$this->check('session_oauth')) {
			return $this->out_message();
		}

		$wechatAccessToken = $_SESSION['oauth']['accessToken'];
		$wechatOpenId = $_SESSION['oauth']['userId'];

		$this->load->model('OAuth_model', 'oauth');

		$input = $this->input->post();

		$provider = $_SESSION['oauth']['provider'];

		$this->db->trans_start();

		// 检查是否已经登入
		if ($this->check_session(TRUE)) {
			log_message('debug', 'binding account: user has logged in.');
			// 如果已存在
			if ($this->oauth->get_one([
				'provider' => $provider,
				'userId' => $_SESSION['oauth']['userId']
			])) {
				log_message('debug', 'binding account: user oauth exists.');
				// 提示用户已经存在
				return $this->out_message(STATUS_BIZ_CONTENT_CONFLICT);
			}

			// 使用当前登入用户
			$account = $_SESSION['user'];
			$id = $_SESSION['user']['id'];
		} else {
			log_message('debug', 'binding account: user has not logged in.');
			// 查询输入手机号的用户
			$account = $this->model->get_one([
				'phone' => $input['phone']
			]);
		}

		// 如果是已有用户且未登入
		if ($account) {
			if (!isset($id)){
				log_message('debug', 'binding account: tring to verify existed user.');
				// 根据输入密码验证身份
				if (!password_verify($input['password'], $account['password'])) {
					return $this->out_message(STATUS_ACCOUNT_PASSWORD_NOT_MATCH);
				}
	
				// 验证通过使用该用户
				$id = $account['id'];
			}
		} else {
			log_message('debug', 'binding account: trying to register user by input.');
			if (!$this->check_input()) {
				return $this->json();
			}

			$account = $this->model->create([
				'phone' => $input['phone'],
				'password' => password_hash($input['password'], PASSWORD_DEFAULT),
				'joinAt' => date('Y-m-d H:i:s')
			], TRUE);

			$id = $account['id'];
		}

		$result = $this->oauth->create([
			'provider' => OAUTH_PROVIDER_WECHAT,
			'userId' => $wechatOpenId,
			'accountId' => $id
		]);

		if (!$result) {
			return out_message(STATUS_OAUTH_ACCOUNT_EXIST);
		}

		$_SESSION['user'] = $account;

		$this->db->trans_complete();

		$url = '/';

		if (isset($_SESSION['redirect'])) {
			$url = $_SESSION['redirect'];
			unset($_SESSION['redirect']);
		}

		return $this->redirect($url);

		// $oauthHandler = "bind_$provider";

		// if (!method_exists($this, $oauthHandler)) {
		// 	return $this->out_message(STATUS_OAUTH_FAIL, $this->lang->line('oauth_login_fail_no_handler'));
		// }

		// return $this->$oauthHandler();
	}

	private function bind_wechat() {

		log_message('debug', "wechat user: $wechatOpenId starting binding...");

		// 需要通过 openid 查询微信用户信息
		// $wechatUserRequest = Requests::get("https://api.weixin.qq.com/sns/userinfo?access_token=$wechatAccessToken&openid=$wechatOpenId&lang=zh_CN");

		// if ($wechatUserRequest->status_code != 200) {
		// 	return $this->out_message(STATUS_SERVER_EXCEPTION);
		// }

		// $wechatUserResponse = json_decode($wechatUserRequest->body, TRUE);

		// if (isset($wechatUserResponse['errcode'])) {
		// 	log_message('error', 'wechat user query failed: [' . $wechatOAuthResponse['errcode'] . '] ' . $wechatOAuthResponse['errmsg']);
		// 	return $this->out_message(STATUS_OAUTH_FAIL, $this->lang->line('oauth_login_fail_wechat'));
		// }

		if (!$this->check_input()) {
			return $this->json();
		}

		$input = $this->input->post();

		$this->db->trans_start();

		$account = $this->model->get_one([
			'phone' => $input['phone']
		]);

		if ($account) {
			if (!password_verify($input['password'], $account['password'])) {
				$this->set_status(STATUS_ACCOUNT_PASSWORD_NOT_MATCH);
				return $this->json();
			}
			$id = $account['id'];
		} else {
			$account = $this->model->create([
				'phone' => $input['phone'],
				'password' => password_hash($input['password'], PASSWORD_DEFAULT),
				'joinAt' => date('Y-m-d H:i:s')
			], TRUE);

			$id = $account['id'];
		}

		$result = $this->oauth->create([
			'provider' => OAUTH_PROVIDER_WECHAT,
			'userId' => $_SESSION['oauth']['userId'],
			'accountId' => $id
		]);

		if (!$result) {
			return out_message(STATUS_OAUTH_ACCOUNT_EXIST);
		}

		$_SESSION['user'] = $account;

		$this->db->trans_complete();

		return $this->json();
	}

	// 创建用户
	public function create() {
		if ($this->check('input')) {
			$input = $this->input->post();

			$query = array(
				'phone' => $input['phone'],
				'password' => password_hash($input['password'], PASSWORD_DEFAULT),
				'joinAt' => date('Y-m-d H:i:s')
			);

			$id = $this->model->create($query);
			if ($id) {
				$this->set_data(array('id' => $id));
				if (isset($_SESSION['oauth'])) {
					$this->load->model('OAuth_model', 'oauth');
					$this->oauth->create([
						'provider' => $_SESSION['oauth']['provider'],
						'userId' => $_SESSION['oauth']['userId'],
						'accountId' => $id
					]);
				}
			} else {
				$this->set_status(STATUS_INPUT_INVALID);
			}
		}

		$this->json();
	}

	public function update($id = NULL) {
		$is_user = $this->check('session');
		if ($is_user) {
			$id = $_SESSION['user']['id'];
		}
		$this->special_input_value = $id;

		if (!$this->check('input')) {
			return $this->json();
		}

		if (!$is_user && !$this->check('admin', 'permission')) {
			return $this->json();
		}

		$input = $this->input->post();
		if (!$this->model->update($id, $input)) {
			$this->set_status(STATUS_SERVER_EXCEPTION);
			return $this->json();
		}

		if ($is_user){
			$input['id'] = $id;
			$_SESSION['user'] = $input;
		}

		return $this->json();
	}

	public function reset() {
		if (!$this->check('session')) {
			return $this->json();
		}

		if (!$this->check('input')) {
			return $this->json();
		}

		$input = $this->input->post();

		$id = $_SESSION['user']['id'];

		$account = $this->model->get($id);
		if (!$account) {
			return $this->out_not_found();
		}

		if (!password_verify($input['passwordOld'], $account['password'])) {
			$this->set_status(STATUS_ACCOUNT_PASSWORD_NOT_MATCH);
			return $this->json();
		}

		if (!$this->model->update($id, array(
			'password' => password_hash($input['password'], PASSWORD_DEFAULT)
		))) {
			$this->set_status(STATUS_SERVER_EXCEPTION);
		}

		return $this->json();
	}
}
