<?php
namespace Darya\ORM;

use Exception;
use ReflectionClass;
use Darya\ORM\Record;
use Darya\Storage\Readable;

/**
 * Darya's abstract entity relation.
 * 
 * TODO: constraint() method for specifying a default filter for related models.
 * TODO: errors() method.
 * TODO: Filter, order, limit, offset for load() and retrieve().
 * TODO: Shouldn't delimitClass() and prepareForeignKey() be static?
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Relation {
	
	const HAS             = 'has';
	const HAS_MANY        = 'has_many';
	const BELONGS_TO      = 'belongs_to';
	const BELONGS_TO_MANY = 'belongs_to_many';
	
	/**
	 * @var \Darya\ORM\Record Parent model
	 */
	protected $parent;
	
	/**
	 * @var \Darya\ORM\Record Target model
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
	 * @var array Filters for constraining related models loaded from storage
	 */
	protected $constraints = array();
	
	/**
	 * @var array|null The related instances
	 */
	protected $related = null;
	
	/**
	 * @var Readable Storage interface
	 */
	protected $storage;
	
	/**
	 * Create a new relation of the given type using the given arguments.
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
		
		return $reflection->newInstanceArgs($arguments);
	}
	
	/**
	 * Instantiate a new relation.
	 * 
	 * @param Record $parent     Parent class
	 * @param string $target     Related class that extends \Darya\ORM\Record
	 * @param string $foreignKey Custom foreign key
	 */
	public function __construct(Record $parent, $target, $foreignKey = null) {
		if (!is_subclass_of($target, 'Darya\ORM\Record')) {
			throw new Exception('Target class not does not extend Darya\ORM\Record');
		}
		
		$this->parent = $parent;
		$this->target = !is_object($target) ? new $target : $target;
		
		$this->foreignKey = $foreignKey;
		$this->setDefaultKeys();
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
	 * Retrieve the default filter for this relation.
	 * 
	 * @return array
	 */
	protected function defaultConstraints() {
		return array(
			$this->foreignKey => $this->parent->id()
		);
	}
	
	/**
	 * Set the default keys for the relation if they haven't already been set.
	 */
	abstract protected function setDefaultKeys();
	
	/**
	 * Helper method for methods that accept single or multiple values.
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
	 * Retrieve the values of the given attribute of the given instances.
	 * 
	 * Works similarly to array_column(), but doesn't return data from any rows
	 * without the given attribute set.
	 * 
	 * @param Record[]|Record $instances
	 * @param string $attribute
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
	 * Generate and set cached related models using the given data.
	 * 
	 * @param int $limit [optional]
	 */
	protected function generate($limit = null) {
		if ($this->related === null) {
			$data = $this->load($limit);
			$class = get_class($this->target);
			$this->related = $class::generate($data);
		}
	}
	
	/**
	 * Reduce the cached related models to those with the given IDs.
	 * 
	 * If no IDs are given then all of the in-memory models will be removed.
	 * 
	 * @param int[] $ids
	 */
	protected function reduce(array $ids = array()) {
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
	 * @param \Darya\ORM\Record $instance
	 */
	protected function replace(Record $instance) {
		$this->verify($instance);
		
		if ($this->related === null) {
			$this->retrieve();
		}
		
		if (!$instance->id()) {
			$this->related[] = $instance;
			
			return;
		}
		
		$replace = null;
		
		foreach ($this->related as $key => $related) {
			if ($related->id() === $instance->id()) {
				$replace = $key;
				
				break;
			}
		}
		
		if ($replace === null) {
			$this->related[] = $instance;
			
			return;
		}
		
		$this->related[$replace] = $instance;
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
	 * Verify that the given objects are all instances of the given class.
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
	 * Retrieve and optionally set the storage used for the target model.
	 * 
	 * Falls back to target model storage, then parent model storage.
	 * 
	 * @param \Darya\Storage\Readable $storage
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
		$this->constraints = $filter;
	}
	
	/**
	 * Retrieve the custom filters used to constrain related models.
	 * 
	 * @return array
	 */
	public function constraints() {
		return $this->constraints;
	}
	
	/**
	 * Retrieve the filter for this relation.
	 * 
	 * @return array
	 */
	public function filter() {
		return array_merge($this->defaultConstraints(), $this->constraints());
	}
	
	/**
	 * Load related model data from storage.
	 * 
	 * TODO: $filter, $order, $offset
	 * 
	 * @return array
	 */
	public function load($limit = null) {
		return $this->storage()->read($this->target->table(), $this->filter(), null, $limit);
	}
	
	/**
	 * Eagerly load the related models for the given parent instances.
	 * 
	 * Returns the given instances with their related models loaded.
	 * 
	 * @param array $instances
	 * @param string $name TODO: Remove this and store as a property
	 * @return array
	 */
	abstract public function eager(array $instances, $name);
	
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
		$this->generate(1);
		
		return !empty($this->related) ? $this->related[0] : null;
	}
	
	/**
	 * Retrieve all related model instances.
	 * 
	 * @return Record[]|null
	 */
	public function all() {
		$this->generate();
		
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
		if (!$this->related) {
			return $this->storage()->count($this->target->table(), $this->filter());
		}
		
		return array_reduce($this->related, function($carry, $item) {
			if ($item instanceof Record) {
				$carry++;
			}
			
			return $carry;
		}, 0);
	}
	
	/**
	 * Set the related models.
	 * 
	 * Clears related models if null is given.
	 * 
	 * @param mixed $instances [optional]
	 */
	public function set($instances = null) {
		if ($instances === null) {
			$this->related = null;
			
			return;
		}
		
		$this->verify($instances);
		$this->related = static::arrayify($instances);
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
