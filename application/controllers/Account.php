<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Account extends MY_Controller {

	var $main_model = 'Account_model';

	public function register() {
		return $this->render('targets/account/register.tpl');
	}

	public function login($oauth = NULL) {
		switch ($oauth) {
			case OAUTH_PROVIDER_WECHAT:
				// wechat login check
				if ($this->json_data['meta']['wechat']) {
					return $this->login_wechat();
				}
				break;
			
			default:
				# code...
				break;
		}

		return $this->render('targets/account/login.tpl');
	}

	private function login_wechat() {
		$this->config->load(OAUTH_PROVIDER_WECHAT, TRUE);

		$wechatConfig = $this->config->item(OAUTH_PROVIDER_WECHAT);
		$wechatAppId = $wechatConfig['app_id'];

		$host = $_SERVER['HTTP_HOST'];
		
		return redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=$wechatAppId&redirect_uri=http%3A%2F%2F$host%2Faccount%2Foauth%2Fwechat&response_type=code&scope=snsapi_userinfo");
	}

	public function my() {
		if (!$this->check('session')) {
			return $this->relogin();
		}

		return $this->out();
	}

	// oauth 用户确认第三方登录后跳转返回至后续处理
	// 分发到各个提供商专用的处理流程
	public function oauth($provider) {
		$this->config->load('oauth2', TRUE);

		$this->lang->load('oauth');

		$oauthHandler = "oauth_$provider";

		if (!method_exists($this, $oauthHandler)) {
			return $this->out_message(STATUS_OAUTH_FAIL, $this->lang->line('oauth_login_fail_no_handler'));
		}

		return $this->$oauthHandler();
	}

	// 微信用户确认第三方登录后处理
	private function oauth_wechat() {
		$this->config->load(OAUTH_PROVIDER_WECHAT, TRUE);

		$wechatConfig = $this->config->item(OAUTH_PROVIDER_WECHAT);
		$wechatAppId = $wechatConfig['app_id'];
		$wechatSecret = $wechatConfig['secret'];

		$wechatCode = $this->input->get('code');

		if (!$wechatCode) {
			return $this->out_message(STATUS_BIZ_ACTION_NOT_ALLOWED);
		}
		
		$wechatOAuthRequest = Requests::get("https://api.weixin.qq.com/sns/oauth2/access_token?appid=$wechatAppId&secret=$wechatSecret&code=$wechatCode&grant_type=authorization_code");

		log_message('debug', $wechatOAuthRequest->body);

		if ($wechatOAuthRequest->status_code != 200) {
			return $this->out_message(STATUS_SERVER_EXCEPTION);
		}

		$wechatOAuthResponse = json_decode($wechatOAuthRequest->body, TRUE);

		if (isset($wechatOAuthResponse['errcode'])) {
			log_message('error', 'wechat oauth failed: [' . $wechatOAuthResponse['errcode'] . '] ' . $wechatOAuthResponse['errmsg']);
			
			switch ($wechatOAuthResponse['errcode']) {
				case 40029:
					return $this->out_message(STATUS_OAUTH_FAIL, $this->lang->line('oauth_login_fail_wechat_code_expired'));
				
				default:
					return $this->out_message(STATUS_OAUTH_FAIL, $this->lang->line('oauth_login_fail_wechat'));
			}
		}

		$wechatAccessToken = $wechatOAuthResponse['access_token'];
		$wechatOpenId = $wechatOAuthResponse['openid'];

		// 需要在 session 中记录 access_token
		$_SESSION['oauth'] = [
			'provider' => OAUTH_PROVIDER_WECHAT,
			'userId' => $wechatOpenId,
			'accessToken' => $wechatAccessToken
		];

		// 比对是否注册过，注册过的查取用户信息写入 session 完成登录
		$this->load->model('OAuth_model', 'oauth');
		$oauth = $this->oauth->get_one([
			'provider' => OAUTH_PROVIDER_WECHAT,
			'userId' => $wechatOpenId
		]);

		// 如果该微信号没有绑定账号
		if (!$oauth) {
			log_message('debug', 'oauth data is not exist in db, go to bind account first.');
			return $this->render('targets/account/bind.tpl');
		}

		log_message('debug', 'oauth data: ' . json_encode($oauth));

		$account = $this->model->get($oauth['accountId']);
		if (!$account) {
			return $this->out_message(STATUS_ACCOUNT_NOT_EXIST);
		}

		$_SESSION['user'] = $account;

		if (isset($_SESSION['redirect'])) {
			$url = $_SESSION['redirect'];
			unset($_SESSION['redirect']);
			return $this->redirect($url);
		} else {
			return $this->redirect('/');
		}
	}
}
