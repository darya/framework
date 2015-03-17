<?php
namespace Darya\Mvc;

use ArrayAccess;
use Countable;
use DateTimeInterface;
use IteratorAggregate;
use Serializable;

/**
 * Darya's abstract model implementation.
 * 
 * TODO: Extract relation handling methods to a Relation class.
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
	 * Instantiate a new model.
	 * 
	 * @param array $data [optional] Set of attributes to set on the model
	 */
	public function __construct(array $data = null) {
		$this->set($data);
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
				case 'array':
				case 'json':
					return json_decode($value, true);
					break;
			}
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
				
				$value = date($this->dateFormat(), (int) $value);
				
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
		} else if ($this->hasRelation($attribute)) {
			return $this->getRelated($attribute);
		}
		
		return $this->access($attribute);
	}
	
	/**
	 * Set the value of a attribute.
	 * 
	 * @param string $key
	 * @param mixed  $value [optional]
	 */
	public function set($key, $value = null) {
		if (is_array($key)) {
			foreach ($key as $attribute => $value) {
				$this->set($attribute, $value);
			}
		} else if ($this->hasRelation($key)) {
			$this->setRelated($key, $value);
		} else {
			$attribute = $this->prepareAttribute($key);
			$this->data[$attribute] = $this->mutate($attribute, $value);
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
	 * Defaults to the current system time if none is given.
	 * 
	 * @param int $time [optional] Timestamp
	 */
	public function setCreatedModified($time = null) {
		$time = $time ?: time();
		$this->setDate($this->prepareAttribute('modified'), $time);
		
		if (!$this->id()) {
			$this->setDate($this->prepareAttribute('created'), $time);
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
