<?php
namespace Darya\Http;

/**
 * Darya's session interface implementation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Session implements SessionInterface {
	
	/**
	 * Start a new session or resume an existing one.
	 * 
	 * @return bool
	 */
	public function start() {
		return session_start();
	}
	
	/**
	 * Determine whether a session is active.
	 *
	 * @return bool
	 */
	public function started() {
		return session_id() != '';
	}
	
	/**
	 * Retrieve a session variable.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
	}
	
	/**
	 * Set a session variable.
	 * 
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set($key, $value) {
		return $_SESSION[$key] = $value;
	}
	
	/**
	 * Delete a session variable.
	 * 
	 * @param string $key
	 * @return mixed Value of the deleted variable
	 */
	public function delete($key) {
		if (isset($_SESSION[$key])) {
			$deleted = $_SESSION[$key];
			unset($_SESSION[$key]);
			return $deleted;
		}
		
		return null;
	}
	
}
