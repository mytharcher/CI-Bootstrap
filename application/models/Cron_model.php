<?php
class Cron_model extends Entity_Model {

	var $main_table = 'Cron';

	function once($key, $timeout) {
		$entity = array(
			'key' => $key,
			'schedule' => date('i G j m *', time() + $timeout),
			'once' => 1
		);
		
		return $this->create($entity);
	}

}
