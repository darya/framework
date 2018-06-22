<?php
namespace Darya\Foundation;

/**
 * Darya's application configuration interface.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Configuration
{
	/**
	 * Determine whether a configuration value exists for the given key.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key);

	/**
	 * Retrieve a configuration value.
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null);

	/**
	 * Set a configuration value.
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set($key, $value);

	/**
	 * Retrieve all of the configuration values.
	 *
	 * @return array
	 */
	public function all();
}
