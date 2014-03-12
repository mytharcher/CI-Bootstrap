<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Index extends MY_Controller {

	function __construct() {
		parent::__construct();
	}

	public function index()
	{
		$this->render("targets/index.tpl", array(
			'title' => 'PHP Bootstrap',
			'body' => 'Hello world!'
		));
	}
	
}
