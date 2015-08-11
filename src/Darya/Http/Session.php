<?php
namespace Darya\Http;

/**
 * Darya's session interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Session {
	
	/**
	 * Create a new session or resume an existing one.
	 * 
	 * @return bool
	 */
	public function start();
	
	/**
	 * Determine whether a session is active.
	 *
	 * @return bool
	 */
	public function started();
	
	/**
	 * Determine whether a session variable is set.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has($key);
	
	/**
	 * Retrieve a session variable.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function get($key);
	
	/**
	 * Set a session variable.
	 * 
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set($key, $value);
	
	/**
	 * Delete a session variable.
	 * 
	 * @param string $key
	 * @return mixed Value of the deleted variable
	 */
	public function delete($key);
	
}
