<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SystemMail extends MY_Controller {

	protected function send($to, $template, $data) {
		$this->config->load('email', TRUE);
		$config = $this->config->item('email');
		$this->load->library('email', $config);
		
		$this->email->from( $config['smtp_account'], $config['sender_name'] );
		$this->email->to( $to );

		$subject = $this->parser->parse($template.'.title.tpl', $data, TRUE);
		$content = $this->parser->parse($template.'.tpl', $data, TRUE);
		$this->email->subject( $subject );
		$this->email->message( $content );
		
		return $this->email->send();
	}
}
