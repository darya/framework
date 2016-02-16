<?php
namespace Darya\ORM;

use Exception;
use ReflectionClass;
use Darya\ORM\Record;
use Darya\Storage\Readable;

/**
 * Darya's abstract entity relation.
 * 
 * TODO: errors() method.
 * TODO: Filter, order, limit, offset for load() and retrieve().
 * TODO: Shouldn't delimitClass() and prepareForeignKey() be static?
 * TODO: Use a $loaded property instead of setting relations to null.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Relation {
	
	const HAS             = 'has';
	const HAS_MANY        = 'has_many';
	const BELONGS_TO      = 'belongs_to';
	const BELONGS_TO_MANY = 'belongs_to_many';
	
	/**
	 * @var string The name of the relation in the context of the parent model
	 */
	protected $name = '';
	
	/**
	 * @var Record Parent model
	 */
	protected $parent;
	
	/**
	 * @var Record Target model
	 */
	protected $target;
	
	/**
	 * @var string Foreign key on the "belongs-to" model
	 */
	protected $foreignKey;
	
	/**
	 * @var string Local key on the "has" model
	 */
	protected $localKey;
	
	/**
	 * @var array Filter for constraining related models loaded from storage
	 */
	protected $constraint = array();
	
	/**
	 * @var Record[]|null The related instances
	 */
	protected $related = null;
	
	/**
	 * @var Readable Storage interface
	 */
	protected $storage;
	
	/**
	 * Helper method for methods that accept single or multiple values, or for
	 * just casting to an array without losing a plain object.
	 * 
	 * Returns a array with the given value as its sole element, if it is not an
	 * array already.
	 * 
	 * @param mixed $value
	 * @return array
	 */
	protected static function arrayify($value) {
		return !is_array($value) ? array($value) : $value;
	}
	
	/**
	 * Separate array elements with numeric keys from those with string keys.
	 * 
	 * @param array $array
	 * @return array array($numeric, $strings)
	 */
	protected static function separateKeys(array $array) {
		$numeric = array();
		$strings = array();
		
		
		foreach ($array as $key => $value) {
			if (is_numeric($key)) {
				$numeric[$key] = $value;
			} else {
				$strings[$key] = $value;
			}
		}
		
		return array($numeric, $strings);
	}
	
	/**
	 * Create a new relation of the given type using the given arguments.
	 * 
	 * Applies numerically-keyed arguments to the constructor and string-keyed
	 * arguments to methods with the same name.
	 * 
	 * @param string $type
	 * @param array  $arguments
	 * @return Relation
	 */
	public static function factory($type = self::HAS, array $arguments) {
		switch ($type) {
			case static::HAS_MANY:
				$class = 'Darya\ORM\Relation\HasMany';
				break;
			case static::BELONGS_TO:
				$class = 'Darya\ORM\Relation\BelongsTo';
				break;
			case static::BELONGS_TO_MANY:
				$class = 'Darya\ORM\Relation\BelongsToMany';
				break;
			default:
				$class = 'Darya\ORM\Relation\Has';
		}
		
		$reflection = new ReflectionClass($class);
		
		list($arguments, $named) = static::separateKeys($arguments);
		
		$instance = $reflection->newInstanceArgs($arguments);
		
		foreach ($named as $method => $argument) {
			if (method_exists($instance, $method)) {
				$argument = static::arrayify($argument);
				call_user_func_array(array($instance, $method), $argument);
			}
		}
		
		return $instance;
	}
	
	/**
	 * Instantiate a new relation.
	 * 
	 * @param Record $parent     Parent class
	 * @param string $target     Related class that extends \Darya\ORM\Record
	 * @param string $foreignKey [optional] Custom foreign key
	 * @param array  $constraint [optional] Constraint filter for related models
	 */
	public function __construct(Record $parent, $target, $foreignKey = null, array $constraint = array()) {
		if (!is_subclass_of($target, 'Darya\ORM\Record')) {
			throw new Exception('Target class not does not extend Darya\ORM\Record');
		}
		
		$this->parent = $parent;
		$this->target = !is_object($target) ? new $target : $target;
		
		$this->foreignKey = $foreignKey;
		$this->setDefaultKeys();
		$this->constrain($constraint);
	}
	
	/**
	 * Lowercase and delimit the given PascalCase class name.
	 * 
	 * @param string $class
	 * @return string
	 */
	protected function delimitClass($class) {
		return preg_replace_callback('/([A-Z])/', function ($matches) {
			return '_' . strtolower($matches[1]);
		}, lcfirst(basename($class)));
	}
	
	/**
	 * Prepare a foreign key from the given class name.
	 * 
	 * @param string $class
	 * @return string
	 */
	protected function prepareForeignKey($class) {
		return $this->delimitClass($class) . '_id';
	}
	
	/**
	 * Retrieve the default filter for the related models.
	 * 
	 * @return array
	 */
	protected function defaultConstraint() {
		return array(
			$this->foreignKey => $this->parent->id()
		);
	}
	
	/**
	 * Set the default keys for the relation if they haven't already been set.
	 */
	abstract protected function setDefaultKeys();
	
	/**
	 * Retrieve the values of the given attribute of the given instances.
	 * 
	 * Works similarly to array_column(), but doesn't return data from any rows
	 * without the given attribute set.
	 * 
	 * @param Record[]|Record|array $instances
	 * @param string                $attribute
	 * @param string                $index     [optional]
	 * @return array
	 */
	protected static function attributeList($instances, $attribute, $index = null) {
		$values = array();
		
		foreach (static::arrayify($instances) as $instance) {
			if (isset($instance[$attribute])) {
				if ($index !== null) {
					$values[$instance[$index]] = $instance[$attribute];
				} else {
					$values[] = $instance[$attribute];
				}
			}
		}
		
		return $values;
	}
	
	/**
	 * Reduce the cached related models to those with the given IDs.
	 * 
	 * If no IDs are given then all of the in-memory models will be removed.
	 * 
	 * @param int[] $ids
	 */
	protected function reduce(array $ids = array()) {
		if (empty($this->related)) {
			return;
		}
		
		$keys = array();
		
		foreach ($this->related as $key => $instance) {
			if (!in_array($instance->id(), $ids)) {
				$keys[$key] = null;
			}
		}
		
		$this->related = array_values(array_diff_key($this->related, $keys));
	}
	
	/**
	 * Replace a cached related model.
	 * 
	 * If the related model does not have an ID or it is not found, it is simply
	 * appended.
	 * 
	 * Retrieves related models if none have been loaded yet.
	 * 
	 * @param Record $instance
	 */
	protected function replace(Record $instance) {
		$this->verify($instance);
		
		$this->retrieve();
		
		if (!$instance->id()) {
			$this->related[] = $instance;
			
			return;
		}
		
		foreach ($this->related as $key => $related) {
			if ($related->id() === $instance->id()) {
				$this->related[$key] = $instance;
				
				return;
			}
		}
		
		$this->related[] = $instance;
	}
	
	/**
	 * Save the given record to storage if it hasn't got an ID.
	 * 
	 * @param Record $instance
	 */
	protected function persist(Record $instance) {
		if (!$instance->id()) {
			$instance->save();
		}
	}
	
	/**
	 * Verify that the given models are instances of the relation's target
	 * class.
	 * 
	 * Throws an exception if any of them aren't.
	 * 
	 * @param Record[]|Record $instances
	 * @throws Exception
	 */
	protected function verify($instances) {
		static::verifyModels($instances, get_class($this->target));
	}
	
	/**
	 * Verify that the given objects are instances of the given class.
	 * 
	 * @param object[]|object $instances
	 * @param string          $class
	 * @throws Exception
	 */
	protected static function verifyModels($instances, $class) {
		if (!class_exists($class)) {
			return;
		}
		
		foreach (static::arrayify($instances) as $instance) {
			if (!$instance instanceof $class) {
				throw new Exception('Related models must be an instance of ' . $class);
			}
		}
	}
	
	/**
	 * Verify that the given models are instances of the relation's parent
	 * class.
	 * 
	 * Throws an exception if any of them aren't.
	 * 
	 * @param Record[]|Record $instances
	 * @throws Exception
	 */
	protected function verifyParents($instances) {
		static::verifyModels($instances, get_class($this->parent));
	}
	
	/**
	 * Retrieve and optionally set the name of the relation on the parent model.
	 * 
	 * @param string $name [optional]
	 * @return string
	 */
	public function name($name = '') {
		$this->name = ((string) $name) ?: $this->name;
		
		return $this->name;
	}
	
	/**
	 * Retrieve and optionally set the storage used for the target model.
	 * 
	 * Falls back to target model storage, then parent model storage.
	 * 
	 * @param Readable $storage
	 */
	public function storage(Readable $storage = null) {
		$this->storage = $storage ?: $this->storage;
		
		return $this->storage ?: $this->target->storage() ?: $this->parent->storage();
	}
	
	/**
	 * Set a filter to constrain which models are considered related.
	 * 
	 * @param array $filter
	 */
	public function constrain(array $filter) {
		$this->constraint = $filter;
	}
	
	/**
	 * Retrieve the custom filter used to constrain related models.
	 * 
	 * @return array
	 */
	public function constraint() {
		return $this->constraint;
	}
	
	/**
	 * Retrieve the filter for this relation.
	 * 
	 * @return array
	 */
	public function filter() {
		return array_merge($this->defaultConstraint(), $this->constraint());
	}
	
	/**
	 * Read related model data from storage.
	 * 
	 * TODO: $filter, $order, $offset
	 * 
	 * @param int $limit [optional]
	 * @return array
	 */
	public function read($limit = 0) {
		return $this->storage()->read($this->target->table(), $this->filter(), null, $limit);
	}
	
	/**
	 * Read, generate and set cached related models from storage.
	 * 
	 * @param int $limit [optional]
	 * @return Record[]
	 */
	public function load($limit = 0) {
		$data = $this->read($limit);
		$class = get_class($this->target);
		$this->related = $class::generate($data);
		
		return $this->related;
	}
	
	/**
	 * Determine whether cached related models have been attempted to be loaded.
	 * 
	 * @return bool
	 */
	public function loaded() {
		return $this->related !== null;
	}
	
	/**
	 * Eagerly load the related models for the given parent instances.
	 * 
	 * Returns the given instances with their related models loaded.
	 * 
	 * @param array $instances
	 * @return array
	 */
	abstract public function eager(array $instances);
	
	/**
	 * Retrieve one or many related model instances, depending on the relation.
	 * 
	 * @return Record[]|Record|null
	 */
	abstract public function retrieve();
	
	/**
	 * Retrieve one related model instance.
	 * 
	 * @return Record|null
	 */
	public function one() {
		if (!$this->loaded()) {
			$this->load(1);
		}
		
		return !empty($this->related) ? $this->related[0] : null;
	}
	
	/**
	 * Retrieve all related model instances.
	 * 
	 * @return Record[]|null
	 */
	public function all() {
		if (!$this->loaded()) {
			$this->load();
		}
		
		return $this->related;
	}
	
	/**
	 * Count the number of related model instances.
	 * 
	 * Counts loaded instances if they are present, queries storage otherwise.
	 * 
	 * @return int
	 */
	public function count() {
		if (!$this->loaded()) {
			return $this->storage()->count($this->target->table(), $this->filter());
		}
		
		return count($this->related);
	}
	
	/**
	 * Set the related models.
	 * 
	 * @param Record[] $instances
	 */
	public function set($instances) {
		$this->verify($instances);
		$this->related = static::arrayify($instances);
	}
	
	/**
	 * Clear the related models.
	 */
	public function clear() {
		$this->related = null;
	}
	
	/**
	 * Read-only access for relation properties.
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
	
}
