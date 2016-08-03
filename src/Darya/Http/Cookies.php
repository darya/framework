<?php
namespace Darya\Http;

/**
 * Darya's HTTP response cookie handler.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Cookies
{
	/**
	 * The cookies data.
	 * 
	 * @var array
	 */
	protected $cookies = array();
	
	/**
	 * Get a property of a cookie.
	 * 
	 * Returns the cookie's value by default. Other properties include 'expire'
	 * and 'path'.
	 * 
	 * @param string $key
	 * @param string $property [optional]
	 * @return string
	 */
	public function get($key, $property = 'value')
	{
		return isset($this->cookies[$key]) && isset($this->cookies[$key][$property]) ? $this->cookies[$key][$property] : null;
	}
	
	/**
	 * Determine whether a cookie with the given key has been set.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return isset($this->cookies[$key]);
	}
	
	/**
	 * Set a cookie to send.
	 * 
	 * @param string $key
	 * @param string $value
	 * @param int    $expire
	 */
	public function set($key, $value, $expire, $path = '/')
	{
		if (is_string($expire)) {
			$expire = strtotime($expire);
		}
		
		$this->cookies[$key] = compact('value', 'expire', 'path');
	}
	
	/**
	 * Get all the cookies. Nom nom.
	 * 
	 * @return array
	 */
	public function all()
	{
		return $this->cookies;
	}
	
	/**
	 * Get all the properties associated with a cookie.
	 * 
	 * @param string $key
	 * @return array
	 */
	public function properties($key)
	{
		return isset($this->cookies[$key]) ? $this->cookies[$key] : array();
	}
	
	/**
	 * Prepare a cookie to be deleted.
	 * 
	 * @param string $key
	 */
	public function delete($key)
	{
		if (isset($this->cookies[$key])) {
			$this->cookies[$key]['value'] = '';
			$this->cookies[$key]['expire'] = 0;
		}
	}
	
	/**
	 * Send the header data for cookies.
	 */
	public function send()
	{
		foreach ($this->cookies as $key => $values) {
			setcookie($key, $values['value'], $values['expire'], $values['path'] ?: '/');
		}
	}
}
