<?php
namespace Darya\Http;

use ArrayAccess;

/**
 * Darya's session interface implementation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Session implements ArrayAccess, SessionInterface {
	
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
	 * @param  mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return $this->has($offset);
	}
	
	/**
	 * @param  mixed $offset
	 * @return bool
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}
	
	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}
	
	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
		$this->delete($offset);
	}
	
	/**
	 * Determine whether a session variable is set.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has($key) {
		return isset($_SESSION[$key]);
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
