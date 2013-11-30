<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation {

	public function error_array()
	{
		return $this->_error_array;
	}

	/**
	 * Match one field to another
	 *
	 * @access	public
	 * @param	string
	 * @param	field
	 * @return	bool
	 */
	public function is_unique($str, $field)
	{
		// var_dump(123);
		list($table, $field)=explode('.', $field);
		list($field, $key)=explode('!', $field);
		$this->CI->db->limit(1)->where($field, $str);
		$value = $this->CI->uri->segment(4);
		if ($key && $value !== FALSE) {
			$this->CI->db->where("$key <>", $value);
		}
		$query = $this->CI->db->get($table);
		
		return $query->num_rows() === 0;
    }
}
