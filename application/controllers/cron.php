<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cron extends Entity_Controller {

	var $main_model = 'Cron_model';
	var $cron_str;

	public function __construct () {
		$this->cron_str = '* * * * * /usr/bin/php '.WEBROOT.'/index.php cron';

		parent::__construct();
	}

	// cron任务主循环，每分钟执行一次
	public function index () {
		if (!$this->input->is_cli_request()) {
			return $this->out_not_found();
		}

		$this->load->helper('crontab');

		$jobs = $this->model->get_all();

		foreach ($jobs as $job) {
			$now = time();
			if (is_time_cron($now, $job['schedule'])) {
				exec('/usr/bin/php '.WEBROOT.'/index.php '.$job['key'], $output, $return);
				echo implode("\n", $output);
				if (!$return && intval($job['once'])) {
					$this->model->delete($job['id']);
				}
			}
		}

		return 0;
	}

	public function all() {
		if ($this->check('session', 'permission')) {
			$jobs = $this->model->get_all();

			$this->set_data('crontab', $jobs);
		}

		$this->json();
	}

	public function status() {
		if ($this->check('session', 'permission')) {
			$this->load->library('CrontabManager', NULL, 'crontab');

			$status = $this->crontab->exists($this->cron_str);

			$this->set_data('status', intval($status));
		}

		$this->json();
	}

	public function start() {
		if ($this->check('session', 'permission')) {
			$this->load->library('CrontabManager', NULL, 'crontab');

			if (!$this->crontab->exists($this->cron_str)) {
				$this->crontab->add($this->crontab->newJob($this->cron_str))->save();
			}
		}

		$this->json();
	}

	public function stop() {
		if ($this->check('session', 'permission')) {
			$this->load->library('CrontabManager', NULL, 'crontab');

			if ($this->crontab->exists($this->cron_str)) {
				$id = $this->crontab->get_id($this->cron_str);

				$this->crontab->deleteJob($id);
				$this->crontab->save(FALSE);
			}
		}

		$this->json();
	}
}
