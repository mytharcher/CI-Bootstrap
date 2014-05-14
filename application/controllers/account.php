<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Account extends Entity_Controller {

	var $main_model = 'Account_model';

	// 获取用户信息
	public function get($id = NULL) {
		$data = NULL;

		if ($this->check('session')) {
			if ($id) {
				if ($this->check('permission')) {
					$data = $this->model->get($id);
				}
			} else {
				$data = $this->model->get($this->session_data['id']);
			}
		}

		$this->json($data);
	}

	// GET /account/all
	public function all() {
		if ($this->check('session', 'permission')) {
			$accounts = $this->model->get_all();
			$this->set_data($accounts);
		}
		
		$this->json();
	}

	protected function relogin() {
		return $this->redirect('account/login');
	}

	public function login()
	{
		$this->render('targets/login.tpl', array('url' => site_url()));
	}
	
	public function oauth($provider = 'weibo')
	{
		$this->config->load('oauth2');
		$allowed_providers = $this->config->item('oauth2');

		if ( ! $provider OR ! isset($allowed_providers[$provider])) {
			// $this->session->set_flashdata('info', '暂不支持'.$provider.'方式登录.');
			// echo "暂不支持$provider方式登录";
			return $this->relogin();
		}

		$this->load->library('oauth2');

		$provider = $this->oauth2->provider($provider, $allowed_providers[$provider]);
		
		$args = $this->input->get();

		if ($args AND !isset($args['code'])) {
			// $this->session->set_flashdata('info', '授权失败了,可能由于应用设置问题或者用户拒绝授权.<br />具体原因:<br />'.json_encode($args));
			// echo '授权失败了,可能由于应用设置问题或者用户拒绝授权.<br />具体原因:<br />'.json_encode($args);
			return $this->relogin();
		}

		$code = $this->input->get('code', TRUE);

		if ( ! $code) {
			try {
				$provider->authorize(array('redirect_uri' => 'http://'.$_SERVER['HTTP_HOST'].'/'.uri_string()));
				return;
				// return $this->redirect('/account/oauth/'.$provider);
			} catch (OAuth2_Exception $e) {
				// $this->session->set_flashdata('info', '操作失败<pre>'.$e.'</pre>');
				// echo '操作失败<pre>'.$e.'</pre>';
			}
		} else {
			try {
				$token = $provider->access($code);
				// $sns_user = $provider->get_user_info($token);
				// 不再依赖从Oauth提供商获取用户信息，改为用户首次登陆填写，绕过备案！
				$sns_user = $provider->make_user($token);

				$account = $this->link($sns_user);
				if ($account) {
					// $this->session->set_flashdata('info', '登录成功');
					// $account['oauth'] = $sns_user;
					$this->session->set_userdata($account);

					// 如果还未完善资料，必须跳转到注册页面完善后才算登录。
					if (isset($account['status']) && $account['status']['id'] == 1) {
						return $this->redirect('account/register');
					}

					$redirect = $this->session->get_redirect();
					if (!$redirect) {
						$redirect = '';
					}

					return $this->redirect($redirect);
				} else {
					// $this->session->set_flashdata('info', '获取用户信息失败');
					// echo '获取用户信息失败';
				}
			} catch (OAuth2_Exception $e) {
				// $this->session->set_flashdata('info', '操作失败<pre>'.$e.'</pre>');
				// echo '操作失败<pre>'.$e.'</pre>';
			}
		}
		return $this->relogin();
	}

	public function register() {
		if (!$this->check('session')) {
			return $this->relogin();
		}

		return $this->render('targets/register.tpl');
	}

	public function logout() {
		$this->session->destroy();

		return $this->redirect('account/login');
	}

	private function link($user) {

		if (is_array($user) && count($user)) {
			$query = array(
				// OAuth ID
				'oid' => $user['uid'],
				// OAuth provider identity
				'provider' => $user['via']
			);

			$account = $this->model->get_one($query);

			// if (!$account) {
			// 	$account = $query;
			// 	$account['joinAt'] = date(DATE_ATOM);
			// 	$account['statusId'] = 0;
			// 	$account['name']   = $user['name'];
			// 	$account['image']  = $user['image'];

			// 	$id = $this->model->create($account);

			// 	if ($id) {
			// 		$account['id'] = $id;
			// 	}
			// } else if ($account['name'] != $user['name'] || $account['image'] != $user['image']) {
			// 	$account['name']   = $user['name'];
			// 	$account['image']  = $user['image'];

			// 	$this->model->update($account['id'], $account);
			// }

			if (!$account) {
				$account = $query;
				$account['joinAt'] = date(DATE_ATOM);

				$source = file_get_contents('http://weibo.com/u/'.$user['uid']);
				// 新浪微博未登录用户引导注册页面不符合HTML标准，“&”符号没有进行转义，会导致解析警告
				$source = str_replace('&', '&amp;', $source);
				$crawled = FALSE;
				if ($source) {
					// CI框架的类库加载问题，任何类加载会被自动实例化，且传入参数必须是数组类型
					$this->load->library('SelectorDOM', array($source));
					// $dom = new SelectorDOM($source);
					$selected = $this->selectordom->select('div.avatar img');
					if (count($selected)) {
						$avatar = $selected[0]['attributes'];
						$account['name'] = $avatar['alt'];
						$account['image'] = $avatar['src'];

						$crawled = TRUE;
					}
				}

				// 如果抓取不成功
				if (!$crawled) {
					$account['statusId'] = 1; // 已注册但未完善资料，不可参与发帖等互动。
				}

				$id = $this->model->create($account);

				if ($id) {
					$account = $this->model->get($id);
				}
			}

			return $account;
		}

		return NULL;
	}

}
