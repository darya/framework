<?php
namespace Darya\Foundation;

use ArrayAccess;
use Darya\Foundation\Configuration;

/**
 * Darya's abstract application configuration implementation (what a mouthful).
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class AbstractConfiguration implements ArrayAccess, Configuration
{
	/**
	 * The configuration data.
	 * 
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * Determine whether an array has a value at the given dot-notated key.
	 * 
	 * @param array  $array
	 * @param string $key
	 * @return bool
	 */
	protected static function arrayHas(array $array, $key)
	{
		if (empty($array) || $key === null) {
			return false;
		}
		
		if (array_key_exists($key, $array)) {
			return true;
		}
		
		$parts = explode('.', $key);
		
		foreach ($parts as $part) {
			if (is_array($array) && array_key_exists($part, $array)) {
				$array = $array[$part];
			} else {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Retrieve a value from the given array using dot notation.
	 * 
	 * @param array  $array
	 * @param string $key
	 * @param mixed  $default [optional]
	 * @return mixed
	 */
	protected static function arrayGet(array $array, $key, $default = null)
	{
		if (empty($array) || $key === null) {
			return $default;
		}
		
		if (array_key_exists($key, $array)) {
			return $array[$key];
		}
		
		$parts = explode('.', $key);
		
		foreach ($parts as $part) {
			if (is_array($array) && array_key_exists($part, $array)) {
				$array = $array[$part];
			} else {
				return $default;
			}
		}
		
		return $array;
	}
	
	/**
	 * Set a value on the given array using dot notation.
	 * 
	 * @param array  $array
	 * @param string $key
	 * @param mixed  $value
	 */
	protected static function arraySet(array &$array, $key, $value)
	{
		if ($key === null) {
			return;
		}
		
		$parts = explode('.', $key);
		
		while (count($parts) > 1) {
			$part = array_shift($parts);
			
			if (!isset($array[$part]) || !is_array($array[$part])) {
				$array[$part] = array();
			}
			
			$array = &$array[$part];
		}
		
		$array[array_shift($parts)] = $value;
	}
	
	/**
	 * Determine whether a configuration value exists for the given key.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return static::arrayHas($this->data, $key);
	}
	
	/**
	 * Retrieve a configuration value.
	 * 
	 * @param string $key
	 * @param mixed  $default [optional]
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		return static::arrayGet($this->data, $key, $default);
	}
	
	/**
	 * Set a configuration value.
	 * 
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set($key, $value)
	{
		static::arraySet($this->data, $key, $value);
	}
	
	/**
	 * Retrieve all of the configuration values.
	 * 
	 * @return array
	 */
	public function all()
	{
		return $this->data;
	}
	
	/**
	 * Determine whether a configuration value exists for the given offset.
	 * 
	 * @param mixed $offset
	 * @return bool
	 */
	 public function offsetExists($offset)
	 {
	     return $this->has($offset);
	 }
	 
	 /**
	  * Retrieve the configuration value at the given offset.
	  * 
	  * @param mixed $offset
	  * @return mixed
	  */
	 public function offsetGet($offset)
	 {
	     return $this->get($offset);
	 }
	 
	 /**
	  * Set a configuration value to the given offset.
	  * 
	  * @param mixed $offset
	  * @param mixed $value
	  */
	 public function offsetSet($offset, $value)
	 {
	     $this->set($offset, $value);
	 }
	 
	 /**
	  * Clear the given offset and its value.
	  * 
	  * @param mixed $offset
	  */
	 public function offsetUnset($offset)
	 {
	     $this->set($offset, null);
	 }
}
