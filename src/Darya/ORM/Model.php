<?php
namespace Darya\ORM;

use ArrayAccess;
use ArrayIterator;
use Countable;
use DateTimeInterface;
use IteratorAggregate;
use Serializable;

/**
 * Darya's abstract model implementation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Model implements ArrayAccess, Countable, IteratorAggregate, Serializable {
	
	/**
	 * @var array Attribute names as keys and types as values
	 */
	protected $attributes = array();
	
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
	 * @var string The attribute that uniquely identifies the model
	 */
	protected $key;
	
	/**
	 * @var array Attributes that have been modified since instantiation
	 */
	protected $changed = array();
	
	/**
	 * Instantiate a new model.
	 * 
	 * @param array $data [optional] Set of attributes to set on the model
	 */
	public function __construct(array $data = null) {
		$this->setMany($data);
		$this->changed = array();
	}
	
	/**
	 * Generate multiple instances of the model using arrays of attributes.
	 * 
	 * @param  array $rows
	 * @return array
	 */
	public static function generate($rows = array()) {
		$instances = array();
		
		foreach ($rows as $key => $attributes) {
			$instances[$key] = new static($attributes);
		}
		
		return $instances;
	}
	
	/**
	 * Recursively convert a model to an array.
	 * 
	 * @param mixed $object
	 * @return array
	 */
	public static function convertToArray($model) {
		if (is_object($model)) {
			if (method_exists($model, 'toArray')) {
				$model = $model->toArray();
			} else {
				$model = (array) $model;
			}
		}
		
		if (is_array($model)) {
			foreach ($model as $key => $value) {
				$model[$key] = $value ? static::convertToArray($value) : $value;
			}
		}
		
		return $model;
	}
	
	/**
	 * Prepare the given attribute name.
	 * 
	 * @param string $attribute
	 * @return string
	 */
	protected function prepareAttribute($attribute) {
		$attribute = strtolower($attribute);
		
		if ($attribute === 'id') {
			return $this->key();
		}
		
		return $attribute;
	}
	
	/**
	 * Retrieve the name of the attribute that uniquely identifies this model.
	 * 
	 * Defaults to 'id' if the `key` property is unset.
	 * 
	 * @return string
	 */
	public function key() {
		if (!isset($this->key)) {
			return 'id';
		}
		
		return $this->prepareAttribute($this->key);
	}
	
	/**
	 * Retrieve the value of the attribute that uniquely identifies this model.
	 * 
	 * @return mixed
	 */
	public function id() {
		return $this->access($this->key());
	}
	
	
	/**
	 * Retrieve the model's raw attributes.
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
		return isset($this->data[$this->prepareAttribute($attribute)]);
	}
	
	/**
	 * Determine whether the given attribute has a defined type.
	 * 
	 * @param string $attribute
	 * @return bool
	 */
	protected function mutable($attribute) {
		return isset($this->attributes[$this->prepareAttribute($attribute)]);
	}
	
	/**
	 * Unmutate the given attribute to be retrieved.
	 * 
	 * @param string $attribute
	 * @return mixed
	 */
	protected function access($attribute) {
		if ($this->has($attribute)) {
			$value = $this->data[$this->prepareAttribute($attribute)];
			
			if (!$this->mutable($attribute)) {
				return $value;
			}
			
			$type = $this->attributes[$attribute];
			
			switch ($type) {
				case 'date':
				case 'datetime':
				case 'time':
					return date($this->dateFormat(), $value);
				case 'array':
				case 'json':
					return json_decode($value, true);
					break;
			}
			
			return $value;
		}
		
		return null;
	}
	
	/**
	 * Mutate the given attribute to be set on the model.
	 * 
	 * @param string $attribute
	 * @param mixed  $value [optional]
	 * @return mixed
	 */
	protected function mutate($attribute, $value = null) {
		if (!$this->mutable($attribute)) {
			return $value;
		}
		
		$type = $this->attributes[$this->prepareAttribute($attribute)];
		
		switch ($type) {
			case 'date':
			case 'datetime':
			case 'time':
				if (is_string($value)) {
					$value = strtotime(str_replace('/', '-', $value));
				}
				
				if ($value instanceof DateTimeInterface) {
					$value = $value->getTimestamp();
				}
				
				break;
			case 'array':
			case 'json':
				if (is_array($value)) {
					$value = json_encode($value);
				}
				
				break;
		}
		
		return $value;
	}
	
	/**
	 * Retrieve the given attribute from the model.
	 * 
	 * @param string $attribute
	 * @return mixed
	 */
	public function get($attribute) {
		if ($attribute === 'id') {
			return $this->id();
		}
		
		return $this->access($attribute);
	}
	
	/**
	 * Set the value of an attribute on the model.
	 * 
	 * If key is an array it will be forwarded to `setMany()`.
	 * 
	 * @param array|string $key
	 * @param mixed        $value [optional]
	 */
	public function set($key, $value = null) {
		if (is_array($key)) {
			return $this->setMany($key);
		}
		
		$attribute = $this->prepareAttribute($key);
		$value     = $this->mutate($attribute, $value);
		
		if (!$this->has($attribute) || $value !== $this->data[$attribute]) {
			$this->data[$attribute] = $value;
			$this->changed = array_merge($this->changed, array($attribute));
		}
	}
	
	/**
	 * Set the values of the given attributes on the model.
	 * 
	 * @param array $values
	 */
	public function setMany($values) {
		foreach ((array) $values as $attribute => $value) {
			$this->set($attribute, $value);
		}
	}
	
	/**
	 * Remove the value of an attribute.
	 * 
	 * @param string $attribute
	 */
	public function remove($attribute) {
		unset($this->data[$this->prepareAttribute($attribute)]);
	}
	
	/**
	 * Retrieve the format to use for date attributes.
	 * 
	 * @return string
	 */
	public function dateFormat() {
		return 'Y-m-d H:i:s';
	}
	
	/**
	 * Set the `created` and `modified` attributes using the given timestamp.
	 * 
	 * Defaults to the current system time if none is given. Only sets `created`
	 * attribute if `id` evaluates to false.
	 * 
	 * @param int $time [optional]
	 */
	public function stamp($time = null) {
		$time = $time ?: time();
		$this->set('modified', $time);
		
		if (!$this->id()) {
			$this->set('created', $time);
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
	 * Retrieve an array of error strings generated by the last validation
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
	 * Determine whether an attribute is set on the model. Shortcut for `set()`.
	 * 
	 * @param string $property
	 * @return bool
	 */
	public function __isset($property) {
		return $this->has($property);
	}
	
	/**
	 * Retrieve an attribute from the model. Shortcut for `get()` and `id()`.
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		return $this->get($property);
	}
	
	/**
	 * Set an attribute's value. Shortcut for `set()`.
	 * 
	 * @param string $property
	 * @param mixed  $value
	 */
	public function __set($property, $value) {
		$this->set($property, $value);
	}
	
	/**
	 * Unset an attribute's value. Shortcut for `remove()`.
	 * 
	 * @param string $property
	 */
	public function __unset($property) {
		$this->remove($property);
	}
	
	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return $this->has($offset);
	}
	
	/**
	 * @param mixed $offset
	 * @return mixed
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
		$this->remove($offset);
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
	 * Prepare the model's attributes for JSON serialization.
	 * 
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->toArray();
	}
	
}