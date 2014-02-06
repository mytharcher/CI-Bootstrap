<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation {

	public function __contruct($rules = array())
	{
		parent::__contruct($rules);

		$this->CI->load->helper('htmlpurifier');
	}

	/**
	 * Run the Validator
	 *
	 * This function does all the work.
	 *
	 * @access	public
	 * @return	bool
	 */
	public function run($group = '')
	{
		// Do we even have any data to process?  Mm?
		// if (count($_POST) == 0)
		// {
		// 	return FALSE;
		// }

		// Does the _field_data array containing the validation rules exist?
		// If not, we look to see if they were assigned via a config file
		if (count($this->_field_data) == 0)
		{
			// No validation rules?  We're done...
			// if (count($this->_config_rules) == 0)
			// {
			// 	return FALSE;
			// }

			// Is there a validation rule for the particular URI being accessed?
			$uri = ($group == '') ? trim($this->CI->uri->uri_string(), '/') : $group;

			if ($uri != '' AND isset($this->_config_rules[$uri]))
			{
				$this->set_rules($this->_config_rules[$uri]);
			}
			else
			{
				$this->set_rules($this->_config_rules);
			}

			// We're we able to set the rules correctly?
			// if (count($this->_field_data) == 0)
			// {
			// 	log_message('debug', "Unable to find validation rules");
			// 	return FALSE;
			// }
		}

		// Load the language file containing error messages
		$this->CI->lang->load('form_validation');

		// Cycle through the rules for each field, match the
		// corresponding $_POST item and test for errors
		foreach ($this->_field_data as $field => $row)
		{
			// Fetch the data from the corresponding $_POST array and cache it in the _field_data array.
			// Depending on whether the field name is an array or a string will determine where we get it from.

			if ($row['is_array'] == TRUE)
			{
				$this->_field_data[$field]['postdata'] = $this->_reduce_array($_POST, $row['keys']);
			}
			else
			{
				if (isset($_POST[$field]) AND $_POST[$field] != "")
				{
					$this->_field_data[$field]['postdata'] = $_POST[$field];
				}
			}

			$this->_execute($row, explode('|', $row['rules']), urldecode($this->_field_data[$field]['postdata']));
		}

		// Did we end up with any errors?
		$total_errors = count($this->_error_array);

		if ($total_errors > 0)
		{
			$this->_safe_form_data = TRUE;
		}

		// Now we need to re-set the POST data with the new, processed data
		$this->_reset_post_array();

		// No errors, validation passes!
		if ($total_errors == 0)
		{
			return TRUE;
		}

		// Validation fails
		return FALSE;
	}

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
		list($table, $field) = explode('.', $field);
		$splited = explode('!', $field);
		$field = $splited[0];
		$key = FALSE;
		if (count($splited) > 1) {
			$key = $splited[1];
		}
		$this->CI->db->limit(1)->where($field, $str);
		$value = $this->CI->special_input_value;
		if ($key && $value !== NULL) {
			$this->CI->db->where("$key <>", $value);
		}
		$query = $this->CI->db->get($table);
		
		return $query->num_rows() === 0;
	}

	public function hour_minute($str)
	{
		$r = preg_match_all("/([01]?[0-9]|2[0-3])\:[0-5][0-9]/", $str);
		return $r !== 0;
	}
}
