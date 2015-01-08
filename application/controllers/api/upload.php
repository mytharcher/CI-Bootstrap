<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Upload extends Entity_Controller {
	var $main_model = 'Upload_model';

	public function index() {
		if ($this->check('session', 'permission')) {
			$data = $this->model->get_all(array(), array(
				'sort' => array('date' => 'desc')
			));
			$this->set_data($data);
		}

		$this->out();
	}

	public function create() {
		if ($this->check('session', 'permission')) {
			$this->load->helper('form');

			$this->config->load('upload', TRUE);
			$upload_base = $this->config->item('upload_path', 'upload');

			$this->load->library('upload', $this->config->item('upload'));

			if ( ! $this->upload->do_upload() ) {
				$this->set_status(5);
				$this->set_data($this->upload->error_msg);
			} else {
				$data = $this->upload->data();
				$record = array(
					'accountId' => $this->session_data['id'],
					'date'      => date('Y-m-d G:i:s'),
					'url'       => "/$upload_base/".$data['file_name'],
					'mime'      => $data['file_type'],
					'isImage'   => intval($data['is_image']),
					'width'     => $data['image_width'],
					'height'    => $data['image_height']
				);

				$id = $this->model->create($record);

				if (!$id) {
					$this->set_status(5);
					unlink($data['full_path']);
				} else {
					$record['id'] = $id;
					$this->set_data($record);
				}
			}
		}

		$this->out();
	}

	// POST /api/$controller/delete/:id?
	public function delete() {
		if ($this->check('session', 'permission')) {

			$this->config->load('upload', TRUE);

			$ids = $this->input->post('id');
			$remove_links = intval($this->input->post('unlink'));
			$files = $this->model->get_list_in('url', $ids);
			if ($this->model->delete_batch($ids, $remove_links)) {
				foreach ($files as $file) {
					$path = $file['url'];
					if (!unlink(WEBROOT."$path")) {
						$this->set_status(5);
					}
				}
			} else {
				$this->set_status(5);
			}
		}

		$this->out();
	}
}
