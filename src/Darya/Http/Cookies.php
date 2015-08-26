<?php
namespace Darya\Http;

/**
 * Darya's HTTP response cookie handler.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Cookies {
	
	/**
	 * The cookies data.
	 * 
	 * @var array
	 */
	protected $cookies = array();
	
	/**
	 * Set a cookie to send.
	 * 
	 * @param string $key
	 * @param string $value
	 * @param int    $expire
	 */
	public function set($key, $value, $expire, $path = '/') {
		$this->cookies[$key] = compact('value', 'expire', 'path');
	}
	
	/**
	 * Get the value of a cookie.
	 * 
	 * @param string $key
	 * @return string
	 */
	public function get($key) {
		return isset($this->cookies[$key]) && isset($this->cookies[$key]['value']) ? $this->cookies[$key]['value'] : null;
	}
	
	/**
	 * Prepare a cookie to be deleted.
	 * 
	 * @param string $key
	 */
	public function delete($key) {
		if (isset($this->cookies[$key])) {
			$this->cookies[$key]['value'] = '';
			$this->cookies[$key]['expire'] = 0;
		}
	}
	
	/**
	 * Send the cookies header data.
	 */
	public function send() {
		foreach ($this->cookies as $key => $values) {
			setcookie($key, $values['value'], $values['expire'], $values['path'] ?: '/');
		}
	}
	
}
