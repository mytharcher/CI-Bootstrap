<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Index extends CI_Controller {

	function __construct() {
		parent::__construct();
	}

	public function index()
	{
		// $this->load->view('index');

		// Load the template from the views directory
		$data = array(
			'title' => 'PHP Bootstrap',
			'body' => 'Hello world!'
		);
		// var_dump($data);
		$this->parser->parse("targets/index.tpl", $data);
	}
}
