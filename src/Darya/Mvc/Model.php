<?php
namespace Darya\Mvc;

use ArrayAccess;
use Darya\Common\Tools;

/**
 * Darya's MVC model.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Model implements ArrayAccess {
	
	/**
	 * @var array Model data
	 */
	protected $data;
	
	/**
	 * @var bool Whether the model has passed validation
	 */
	protected $valid = false;
	
	/**
	 * @var array Errors that occured with validation
	 */
	protected $errors = array();
	
	/**
	 * @var string The key of the field that uniquely identifies the model
	 */
	protected $key; 
	
	/**
	 * @var string A prefix for model data keys 
	 */
	protected $fieldPrefix;
	
	/**
	 * Instantiates a new model
	 * 
	 * @param array $data Set of properties to set on the model
	 */
	public function __construct($data = null) {
		if ($data && is_array($data)) {
			$this->setAll($data);
		}
	}
	
	/**
	 * Return the base name of the current class (static)
	 * 
	 * @return string
	 */
	public static function basename() {
		return basename(str_replace('\\', '/', get_class(new static)));
	}
	
	/**
	 * Generates multiple instances using an array of data arrays
	 * 
	 * @param  array $rows
	 * @return array Instances generated
	 */
	public static function output($rows = array()) {
		$instances = array();
		
		foreach ($rows as $k => $row) {
			$instances[] = new static($row);
		}
		
		return $instances;
	}
	
	/**
	 * Returns the prefix for properties of this model
	 * 
	 * @return string
	 */
	public function getFieldPrefix() {
		return !is_null($this->fieldPrefix) ? $this->fieldPrefix : Tools::camelToDelim(static::basename(), '_') . '_';
	}
	
	/**
	 * Returns the name of the property that represents the unique ID of this model
	 * Uses "{fieldPrefix}id" if key is unset.
	 * 
	 * @return string
	 */
	public function getKey() {
		return !is_null($this->key) ? $this->key : $this->getFieldPrefix() . 'id';
	}
	
	/**
	 * Returns the unique ID of this model
	 * 
	 * @return mixed
	 */
	public function getId() {
		return $this->get($this->getKey());
	}
	
	/**
	 * Returns whether a property is set on the model
	 * 
	 * @param string $property
	 * @return bool
	 */
	public function __isset($property) {
		return isset($this->data[strtolower($property)]) || (property_exists($this, $property) && isset($this->$property) && !is_null($this->$property));
	}
	
	/**
	 * Get a property from the model. Essentially a
	 * shortcut for get() and getId().
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		if ($property == 'id') {
			return $this->getId();
		} else {
			if (property_exists($this, $property)) {
				return $this->$property;
			} else {
				return $this->get($property);
			}
		}
	}
	
	/**
	 * @param  mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return isset($this->$offset);
	}
	
	/**
	 * @param  mixed $offset
	 * @return bool
	 */
	public function offsetGet($offset) {
		return $this->$offset;
	}
	
	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
		return $this->set($offset, $value);
	}
	
	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
		unset($this->data[strtolower($offset)]);
	}
	
	/**
	 * Get a property from the model
	 * 
	 * @param  string $property
	 * @return mixed
	 */
	public function get($property) {
		$property = strtolower($property);
		$value = false;
		
		if (isset($this->data[$property])) {
			$value = $this->data[$property];
		} else if (isset($this->data[$this->getFieldPrefix() . $property])) {
			$value = $this->data[$this->getFieldPrefix() . $property];
		}
		
		return $value;
	}
	
	/**
	 * Retrieve all properties from the model
	 * 
	 * @return array
	 */
	public function getAll() {
		$data = array();
		
		foreach ($this->data as $key => $value) {
			$data[$key] = $value;
		}
		
		return $data;
	}
	
	/**
	 * Return a field as an integer
	 * 
	 * @param  string $key
	 * @return int
	 */
	public function getInt($key) {
		return (int)$this->get($key);
	}
	
	/**
	 * Return a field in the configured time format
	 *
	 * @param  string $key
	 * @param  string $format
	 * @return string
	 */
	public function getTime($key, $format = null) {
		return date($format ? $format : 'H:i:s', strtotime($this->get($key)));
	}
	
	/**
	 * Return a field in the configured date format
	 *
	 * @param  string $key
	 * @param  string $format
	 * @return string
	 */
	public function getDate($key, $format = null) {
		return date($format ? $format : "jS M 'y", strtotime($this->get($key)));
	}
	
	/**
	 * Return a field in the configured date/time format
	 *
	 * @param  string $key
	 * @param  string $format
	 * @return string
	 */
	public function getDateTime($key, $format = null) {
		return date($format ? $format : 'd/m/y H:i', strtotime($this->get($key)));
	}
	
	/**
	 * Set the value of a field
	 * 
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set($key, $value) {
		$key = strtolower($key);
		$this->data[$key] = $value;
	}
	
	/**
	 * Set the values of multiple fields using a key/value array
	 * 
	 * @param array $data
	 */
	public function setAll($data = array()) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->set($key, $value);
			}
		}
	}
	
	/**
	 * Set the value of a date field with the correct formatting for MySQL
	 * TODO: This should not be specific to MySQL, create a config for model date setting format
	 * 
	 * @param string $key
	 * @param string $date Date to be parsed using strtotime()
	 */
	public function setDate($key, $date) {
		$this->set($key, date('Y-m-d H:i:s', is_string($date) ? strtotime(str_replace('/','-',$date)) : $date));
	}
	
	/**
	 * Sets the created and modified dates according to the time given
	 * Defaults to the current system time if none is given
	 * 
	 * @param int $time [optional] Timestamp
	 */
	public function setCreatedModified($time = null) {
		$time = $time ?: time();
		$this->setDate($this->getFieldPrefix() . 'modified', $time);
		
		if (!$this->getId()) {
			$this->setDate($this->getFieldPrefix() . 'created', $time);
		}
	}
	
	/**
	 * Validates all of the properties of the model
	 * 
	 * @return bool
	 */
	public function validate() {
		return $this->valid = count($this->errors);
	}
	
	/**
	 * Returns an array of error strings from the previous attempt at validation
	 * 
	 * @return array
	 */
	public function errors() {
		return $this->errors;
	}
	
}
