<?php
namespace Darya\Http\Session;

use ArrayAccess;
use Darya\Http\Session;

/**
 * Darya's PHP session implementation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Php implements ArrayAccess, Session {
	
	/**
	 * Magic method that determines whether a session key is set.
	 * 
	 * @param string $property
	 * @return bool
	 */
	public function __isset($property) {
		return $this->has($property);
	}
	
	/**
	 * Magic method that retrieves a session value.
	 * 
	 * @param string $property
	 * @return bool
	 */
	public function __get($property) {
		return $this->get($property);
	}
	
	/**
	 * Magic method that sets a session value.
	 * 
	 * @param string $property
	 * @param mixed  $value
	 */
	public function __set($property, $value) {
		$this->set($property, $value);
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
