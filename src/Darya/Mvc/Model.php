<?php
namespace Darya\Mvc;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Serializable;
use Darya\Common\Tools;

/**
 * Darya's abstract model implementation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Model implements ArrayAccess, Countable, IteratorAggregate, Serializable {
	
	/**
	 * @var array Attribute names and types
	 */
	protected $attributes = array();
	
	/**
	 * @var array Relationships to other models
	 */
	protected $relations = array();
	
	/**
	 * @var array Types for casting attributes when setting and getting values
	 */
	protected $mutations = array();
	
	/**
	 * @var array Model data
	 */
	protected $data;
	
	/**
	 * @var bool Whether the model is currently in a valid state
	 */
	protected $valid = false;
	
	/**
	 * @var array Errors that occured with validation
	 */
	protected $errors = array();
	
	/**
	 * @var string The attribute that uniquely identifies the model, if any
	 */
	protected $key;
	
	/**
	 * @var string Prefix for model attributes
	 */
	protected $prefix;
	
	/**
	 * @var bool Whether to use the model's class name to prefix attributes if a
	 *           custom prefix is not set
	 */
	protected $classPrefix = true;
	
	/**
	 * Instantiate a new model.
	 * 
	 * @param array $data [optional] Set of attributes to set on the model
	 */
	public function __construct(array $data = null) {
		$this->set($data);
	}
	
	/**
	 * Parse the given data type definition.
	 * 
	 * @param string $type
	 * @return string
	 */
	public static function parseType($type) {
		return strtolower($type);
	}
	
	/**
	 * Retrieve the base name of the current class.
	 * 
	 * @return string
	 */
	public static function basename() {
		return basename(str_replace('\\', '/', get_class(new static)));
	}
	
	/**
	 * Generate multiple instances of the model using multiple data arrays.
	 * 
	 * @param  array $rows
	 * @return array
	 */
	public static function output($rows = array()) {
		$instances = array();
		
		foreach ($rows as $key => $row) {
			$instances[$key] = new static($row);
		}
		
		return $instances;
	}
	
	/**
	 * Recursively convert a model to an array. If no object is given, the
	 * model is assumed as the object.
	 * 
	 * @param mixed $object
	 * @return array
	 */
	public static function convertToArray($model = null) {
		if (is_object($model)) {
			if (method_exists($model, 'toArray')) {
				$model = $model->toArray();
			} else {
				$model = (array) $model;
			}
		}
		
		if (is_array($model)) {
			foreach ($model as $key => $value) {
				$model[$key] = $value ? static::toArray($value) : $value;
			}
		}
		
		return $model;
	}
	
	/**
	 * Retrieve the prefix for this model's attributes.
	 * 
	 * @return string
	 */
	public function prefix() {
		if ($this->prefix !== null) {
			return $this->prefix;
		}
		
		if ($this->classPrefix) {
			return Tools::camelToDelim(static::basename(), '_') . '_';
		}
		
		return '';
	}
	
	/**
	 * Retrieve the name of the attribute that uniquely identifies this model.
	 * 
	 * Defaults to `id` preceded by the attribute prefix if `key` is unset.
	 * 
	 * @return string
	 */
	public function key() {
		return !is_null($this->key) ? $this->key : $this->prefix() . 'id';
	}
	
	/**
	 * Retrieve the attribute that uniquely identifies this model.
	 * 
	 * @return mixed
	 */
	public function id() {
		return $this->get($this->key());
	}
	
	/**
	 * Determine whether a property is set on the model
	 * 
	 * @param string $property
	 * @return bool
	 */
	public function __isset($property) {
		return isset($this->data[strtolower($property)]) || (property_exists($this, $property) && isset($this->$property) && !is_null($this->$property));
	}
	
	/**
	 * Get a property from the model. Shortcut for `get()` and `id()`.
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		if ($property == 'id') {
			return $this->id();
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
		$this->set($offset, $value);
	}
	
	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
		unset($this->data[strtolower($offset)]);
	}
	
	/**
	 * @return int
	 */
	public function count() {
		return count($this->data);
	}
	
	/**
	 * @return \Traversable
	 */
	public function getIterator() {
		return new ArrayIterator($this->data);
	}
	
	/**
	 * Serialize the model.
	 * 
	 * @return string
	 */
	public function serialize() {
		return serialize($this->data);
	}
	
	/**
	 * Unserialize the model.
	 * 
	 * @param string $serialized
	 */
	public function unserialize($serialized) {
		$this->data = unserialize($serialized);
	}
	
	/**
	 * Retrieve all of the model's attributes.
	 * 
	 * @return array
	 */
	public function data() {
		return $this->data;
	}
	
	/**
	 * Determine whether the given attribute is set on the model.
	 * 
	 * @param string $attribute
	 * @return bool
	 */
	public function has($attribute) {
		$attribute = strtolower($attribute);
		return isset($this->data[$this->prefix() . $attribute]);
	}
	
	/**
	 * Get a property from the model
	 * 
	 * @param string $attribute
	 * @return mixed
	 */
	public function get($attribute) {
		$property = strtolower($attribute);
		
		if (isset($this->data[$attribute])) {
			return $this->data[$attribute];
		} else if (isset($this->data[$this->prefix() . $attribute])) {
			return $this->data[$this->prefix() . $attribute];
		}
		
		return null;
	}
	
	/**
	 * Determine whether the given attribute has a type and is mutatable.
	 * 
	 * @return bool
	 */
	protected function mutable($attribute) {
		return isset($this->attributes[$attribute]) && isset($this->mutations[$attribute]);
	}
	
	/**
	 * Retrieve the given attribute casted.
	 * 
	 * @param string $attribute
	 * @return mixed
	 */
	protected function castGet($attribute) {
		if ($this->has($attribute)) {
			$value = $this->data[$attribute];
			
			if ($this->mutable($attribute)) {
				$type = $this->attributes[$attribute];
				$mutation = $this->mutations[$attribute];
				
				switch ($type) {
					case 'array':
						return json_decode($value);
						break;
				}
			}
			
			return $value;
		}
		
		return null;
	}
	
	/**
	 * Cast the given attribute to set.
	 * 
	 * @param string $attribute
	 * @param mixed  $value [optional]
	 * @return mixed
	 */
	protected function castSet($attribute, $value = null) {
		if ($this->mutable($attribute)) {
			$type = $this->attributes[$attribute];
			$mutation = $this->mutations[$attribute];
			
			switch ($type) {
				case 'date': case 'datetime': case 'time':
					if (!$value instanceof DateTimeInterface) {
						$value = new DateTime($value) ?: new DateTime("@$value");
					}
					
					$value = $value->getTimestamp();
					break;
				case 'array':
					if (is_array($value)) {
						$value = json_encode($value);
					}
					break;
			}
		}
		
		$this->data[$attribute] = $value;
		
		return $value;
	}
	
	/**
	 * Set the value of a attribute.
	 * 
	 * @param string $attribute
	 * @param mixed  $value [optional]
	 */
	public function set($key, $value) {
		if (is_array($key)) {
			foreach ($key as $attribute => $value) {
				$this->set($attribute, $value);
			}
		} else {
			$attribute = strtolower($key);
			$this->data[$attribute] = $this->castSet($value);
		}
	}
	
	/**
	 * Set the value of a date attribute with the correct formatting for MySQL.
	 * 
	 * TODO: This does not need to be specific to MySQL. Create a config for
	 *       model date formats?
	 * 
	 * @param string $key
	 * @param string $date Date to be parsed using strtotime()
	 */
	public function setDate($key, $date) {
		$this->set($key, date('Y-m-d H:i:s', is_string($date) ? strtotime(str_replace('/', '-', $date)) : $date));
	}
	
	/**
	 * Set the `created` and `modified` attributes using the given timestamp.
	 * 
	 * Defaults to the current system time if none is given.
	 * 
	 * @param int $time [optional] Timestamp
	 */
	public function setCreatedModified($time = null) {
		$time = $time ?: time();
		$this->setDate($this->prefix() . 'modified', $time);
		
		if (!$this->id()) {
			$this->setDate($this->prefix() . 'created', $time);
		}
	}
	
	/**
	 * Validate all of the model's attributes.
	 * 
	 * @return bool
	 */
	public function validate() {
		return $this->valid = !count($this->errors);
	}
	
	/**
	 * Retrieve an array of error strings generate by the last validation
	 * attempt.
	 * 
	 * @return array
	 */
	public function errors() {
		return $this->errors;
	}
	
	/**
	 * Recursively convert an object to an array. If no object is given, the
	 * model is assumed as the object.
	 * 
	 * @param mixed $object
	 * @return array
	 */
	public function toArray($object = null) {
		return static::convertToArray($this->data);
	}
	
	/**
	 * Serialize the model as a JSON string.
	 * 
	 * @return string
	 */
	public function toJson() {
		return json_encode($this->jsonSerialize());
	}
	
	/**
	 * Prepare the model's attributes for JSON serialization.
	 * 
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->toArray();
	}
}
