<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * @package		php-bootstrap
 * @author		mytharcher
 * @copyright	Copyright (c) since 2013
 * @license		MIT Licenced
 */

// ------------------------------------------------------------------------

/**
 * Session Class
 */
class MY_Session {

	var $sess_encrypt_cookie		= FALSE;
	var $sess_use_database			= FALSE;
	var $sess_table_name			= '';
	var $sess_expiration			= 7200;
	var $sess_expire_on_close		= FALSE;
	var $sess_match_ip				= FALSE;
	var $sess_match_useragent		= TRUE;
	var $sess_cookie_name			= 'Session';
	var $cookie_expiration			= 0;
	var $cookie_path				= '';
	var $cookie_domain				= '';
	var $cookie_secure				= FALSE;
	var $sess_time_to_update		= 300;
	var $gc_probability				= 5;
	var $CI;
	var $now;

	/**
	 * Session Constructor
	 *
	 * The constructor runs the session routines automatically
	 * whenever the class is instantiated.
	 */
	public function __construct($params = array()) {
		log_message('debug', "Session Class Initialized");

		// Set the super object to a local variable for use throughout the class
		$this->CI =& get_instance();

		// Set all the session preferences, which can either be set
		// manually via the $params array above or via the config file
		foreach (array('sess_encrypt_cookie', 'sess_use_database', 'sess_table_name', 'sess_expiration', 'sess_expire_on_close', 'sess_match_ip', 'sess_match_useragent', 'sess_cookie_name', 'cookie_path', 'cookie_domain', 'cookie_secure', 'sess_time_to_update', 'time_reference', 'cookie_expiration', 'encryption_key') as $key) {
			$this->$key = (isset($params[$key])) ? $params[$key] : $this->CI->config->item($key);
		}

		// Load the string helper so we can use the strip_slashes() function
		$this->CI->load->helper('string');

		// Are we using a database?  If so, load it
		if ($this->sess_use_database === TRUE AND $this->sess_table_name != '') {
			$this->CI->load->database();
		}

		// Set the "now" time.  Can either be GMT or server time, based on the
		// config prefs.  We use this to set the "last activity" time
		$this->now = $this->_get_time();

		// Set the session length. If the session expiration is
		// set to zero we'll set the expiration two years from now.
		if ($this->sess_expiration == 0) {
			$this->sess_expiration = (60*60*24);
		}

		if ($this->cookie_expiration == 0) {
			$this->cookie_expiration = (60*60*24*365*2);
		}

		// Run the Session routine. If a token doesn't exist we'll
		// create a new one.
		if ( ! $this->token_get()) {
			$this->token_set();
		}

		// Delete expired sessions if necessary
		$this->_sess_gc();

		log_message('debug', "Session routines successfully run");
	}

	// Fetch token string from client cookie
	function token_get () {
		return $this->CI->input->cookie($this->sess_cookie_name);
	}

	// Set new token to client cookie
	function token_set () {
		$sessid = '';
		while (strlen($sessid) < 32) {
			$sessid .= mt_rand(0, mt_getrandmax());
		}

		$this->_set_cookie(md5($sessid));
	}

	function query () {
		// Must use DB
		if ($this->sess_use_database === FALSE) {
			return FALSE;
		}

		$token = $this->token_get();

		$this->CI->db->where('id', $token);

		if ($this->sess_match_ip == TRUE) {
			$this->CI->db->where('ip', $this->CI->input->ip_address());
		}

		if ($this->sess_match_useragent == TRUE) {
			$this->CI->db->where('userAgent', substr($this->CI->input->user_agent(), 0, 120));
		}

		$query = $this->CI->db->get($this->sess_table_name);

		// No result?  Kill it!
		if ($query->num_rows() == 0) {
			return FALSE;
		}

		return $query->row();
	}

	function get ($auto_update = FALSE) {
		// Must use DB
		if (! $this->sess_use_database) {
			return FALSE;
		}

		$token = $this->token_get();

		// No cookie?  Goodbye cruel world!...
		if ($token === FALSE) {
			log_message('debug', 'A session cookie was not found.');
			return FALSE;
		}

		$session = $this->query();

		if ($session === FALSE) {
			return FALSE;
		}

		if ($session->last_activity + $this->sess_expiration < $this->now) {
			$this->destroy();
			return FALSE;
		}

		if ($auto_update) {
			$this->update();
		}

		return json_decode($session->user_data, true);
	}

	// --------------------------------------------------------------------

	/**
	 * Write the session data
	 *
	 * @access	public
	 * @return	void
	 */
	function update($data = NULL) {
		// Must use DB
		if ($this->sess_use_database === FALSE) {
			return;
		}

		$token = $this->token_get();

		$session_record = array(
			'lastActivity' => $this->now
		);

		if (isset($data)) {
			$session_record['userData'] = json_encode($data);
		}

		// Run the update query
		$this->CI->db->where('id', $token);
		$this->CI->db->update($this->sess_table_name, $session_record);
	}

	// --------------------------------------------------------------------

	/**
	 * Create a new session
	 *
	 * @access	public
	 * @return	void
	 */
	function create($data = array()) {
		// Must use DB
		if ($this->sess_use_database === FALSE) {
			return;
		}

		$session = array(
			'id'	=> $this->token_get(),
			'ip'	=> $this->CI->input->ip_address(),
			'userAgent'	=> substr($this->CI->input->user_agent(), 0, 120),
			'lastActivity'	=> $this->now,
			'userData'		=> json_encode($data)
		);

		$this->CI->db->query($this->CI->db->insert_string($this->sess_table_name, $session));
	}

	// --------------------------------------------------------------------

	/**
	 * Destroy the current session
	 *
	 * @access	public
	 * @return	void
	 */
	function destroy($id = NULL)
	{
		$token = $this->token_get();
		// Kill the session DB row
		if ($this->sess_use_database === TRUE && ($id || $token)) {
			if ($id) {
				$this->CI->db->like('userData', '"id":"'.$id.'"');
			} else if ($token) {
				$this->CI->db->where('id', $token);
			}
			$this->CI->db->delete($this->sess_table_name);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get the "now" time
	 *
	 * @access	private
	 * @return	string
	 */
	function _get_time()
	{
		if (strtolower($this->time_reference) == 'gmt')
		{
			$now = time();
			$time = mktime(gmdate("H", $now), gmdate("i", $now), gmdate("s", $now), gmdate("m", $now), gmdate("d", $now), gmdate("Y", $now));
		}
		else
		{
			$time = time();
		}

		return $time;
	}

	// --------------------------------------------------------------------

	/**
	 * Write the session cookie
	 *
	 * @access	public
	 * @return	void
	 */
	function _set_cookie($cookie_data)
	{

		$expire = ($this->sess_expire_on_close === TRUE) ? 0 : $this->cookie_expiration + time();

		// Set the cookie
		setcookie(
					$this->sess_cookie_name,
					$cookie_data,
					$expire,
					$this->cookie_path,
					$this->cookie_domain,
					$this->cookie_secure
				);
	}

	// --------------------------------------------------------------------

	/**
	 * Garbage collection
	 *
	 * This deletes expired session rows from database
	 * if the probability percentage is met
	 *
	 * @access	public
	 * @return	void
	 */
	function _sess_gc()
	{
		if (! $this->sess_use_database) {
			return;
		}

		srand(time());
		if ((rand() % 100) < $this->gc_probability)
		{
			$expire = $this->now - $this->sess_expiration;

			$this->CI->db->where("lastActivity < {$expire}");
			$this->CI->db->delete($this->sess_table_name);

			log_message('debug', 'Session garbage collection performed.');
		}
	}


}
// END Session Class

/* End of file Session.php */
/* Location: ./system/libraries/Session.php */