<?php
namespace Darya\ORM;

use ReflectionClass;
use Darya\ORM\Model;
use Darya\ORM\Relation;
use Darya\Storage\Readable;
use Darya\Storage\Modifiable;
use Darya\Storage\Searchable;

/**
 * Darya's active record implementation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Record extends Model {
	
	/**
	 * Overrides the name of the database table that persists the model. The
	 * model's lowercased class name is used if this is not set.
	 * 
	 * @var string Database table name
	 */
	protected $table;
	
	/**
	 * @var \Darya\Storage\Readable Instance storage
	 */
	protected $storage;
	
	/**
	 * @var \Darya\Storage\Readable Shared storage
	 */
	protected static $sharedStorage;
	
	/**
	 * @var array Definitions of related models
	 */
	protected $relations = array();
	
	/**
	 * @var array Related model data
	 */
	protected $related = array();
	
	/**
	 * Instantiate a new record with the given data or load an instance from
	 * storage if the given data is a valid primary key.
	 * 
	 * @param mixed $data An array of key-value attributes to set or a primary key to load by
	 */
	public function __construct($data = null) {
		if (is_array($data)) {
			$this->set($data);
		} else if(is_numeric($data) || is_string($data)) {
			$this->data = static::load($data);
		}
	}
	
	/**
	 * Determine whether the given attribute or relation is set on the record.
	 * 
	 * @param string $attribute
	 */
	public function has($attribute) {
		return $this->hasRelated($attribute) || parent::has($attribute);
	}
	
	/**
	 * Retrieve the given attribute or relation from the record.
	 * 
	 * @param string $attribute
	 * @return mixed
	 */
	public function get($attribute) {
		if ($this->hasRelation($attribute)) {
			return $this->related($attribute);
		}
		
		return parent::get($attribute);
	}
	
	/**
	 * Set the value of an attribute or relation on the model.
	 * 
	 * @param string $attribute
	 * @param mixed  $value
	 */
	public function set($attribute, $value = null) {
		if (is_string($attribute) && $this->hasRelation($attribute)) {
			return $this->setRelated($attribute, $value);
		}
		
		parent::set($attribute, $value);
	}
	
	/**
	 * Returns the name of the database table this record belongs to.
	 * 
	 * If none is set, it defaults to creating it from the class name.
	 * 
	 * For example:
	 *     Page        -> pages
	 *     PageSection -> page_sections
	 */
	public function table() {
		if ($this->table) {
			return $this->table;
		}
		
		return preg_replace_callback('/([A-Z])/', function ($matches) {
			return '_' . strtolower($matches[1]);
		}, lcfirst(static::basename())) . 's';
	}
	
	/**
	 * Get and optionally set the model's storage instance.
	 * 
	 * @return \Darya\Storage\Readable
	 */
	public function storage(Readable $storage = null) {
		$this->storage = $storage ?: $this->storage;
		
		return $this->storage ?: static::getSharedStorage();
	}
	
	/**
	 * Get the storage shared to all instances of this model.
	 * 
	 * @return \Darya\Storage\Readable
	 */
	public static function getSharedStorage() {
		return static::$sharedStorage;
	}
	
	/**
	 * Share the given database connection to all instances of this model.
	 * 
	 * @param \Darya\Storage\Readable $storage
	 */
	public static function setSharedStorage(Readable $storage) {
		static::$sharedStorage = $storage;
	}
	
	/**
	 * Prepare the record's data for storage. This is here until repositories
	 * are implemented.
	 * 
	 * @return array
	 */
	protected function prepareData() {
		$types = $this->attributes;
		
		$data = array_intersect_key($this->data, $this->attributes) ?: $this->data;
		
		foreach ($data as $attribute => $value) {
			if (isset($types[$attribute])) {
				$type = $types[$attribute];
				
				switch ($type) {
					case 'date':
						$value = date('Y-m-d', $value);
						break;
					case 'datetime':
						$value = date('Y-m-d H:i:s', $value);
						break;
					case 'time':
						$value = date('H:i:s', $value);
						break;
				}
				
				$data[$attribute] = $value;
			}
		}
		
		return $data;
	}
	
	/**
	 * Prepare the given filter.
	 * 
	 * Creates a filter for the record's key attribute if the given value is not
	 * an array.
	 * 
	 * @param mixed  $filters
	 * @param string $operator
	 * @param bool   $excludeWhere
	 * @return string
	 */
	protected static function prepareFilter($filter) {
		if (!is_array($filter)) {
			$instance = new static;
			$filter = array($instance->key() => $filter);
		}
		
		return $filter;
	}
	
	/**
	 * Load record data from storage using the given criteria.
	 * 
	 * @param array|string|int $filter [optional]
	 * @param array|string     $order  [optional]
	 * @param int              $limit  [optional]
	 * @param int              $offset [optional]
	 * @return array
	 */
	public static function load($filter = array(), $order = array(), $limit = null, $offset = 0){
		$instance = new static;
		$storage = $instance->storage();
		$filter = static::prepareFilter($filter);
		return $storage->read($instance->table(), $filter, $order, $limit, $offset);
	}
	
	/**
	 * Load a record instance from storage using the given criteria.
	 * 
	 * Returns false if the record cannot be found.
	 * 
	 * @param array|string|int $filter [optional]
	 * @param array|string     $order  [optional]
	 * @return Record|bool
	 */
	public static function find($filter = array(), $order = array()) {
		$data = static::load($filter, $order, 1);
		return $data && isset($data[0]) ? new static($data[0]) : false;
	}
	
	/**
	 * Load a record instance from storage using the given criteria or create a
	 * new instance if nothing is found.
	 * 
	 * @param array|string|int $filter [optional]
	 * @param array|string     $order  [optional]
	 * @return Record
	 */
	public static function findOrNew($filter = array(), $order = array()) {
		$instance = static::one($filter, $order);
		return $instance === false ? new static : $instance;
	}
	
	/**
	 * Load multiple record instances from storage using the given criteria.
	 * 
	 * @static
	 * @param mixed $filters
	 * @param array|string $orders
	 * @return array
	 */
	public static function all($filter = array(), $order = array(), $limit = null, $offset = 0) {
		return static::generate(static::load($filter, $order, $limit, $offset));
	}
	
	/**
	 * Search for record instances in storage using the given criteria.
	 * 
	 * @param string           $query
	 * @param array            $attributes
	 * @param array|string|int $filter
	 * @param array|string     $order
	 * @param int              $limit
	 * @param int              $offset
	 * @return array
	 */
	public static function search($query, $attributes = array(), $filter = array(), $order = array(), $limit = null, $offset = 0) {
		$instance = new static;
		$storage = $instance->storage();
		
		if (!$storage instanceof Searchable) {
			throw new \Exception(get_class($instance) . ' storage is not searchable');
		}
		
		$data = $storage->search($instance->table(), $query, $attributes, $filter, $order, $limit, $offset);
		
		return static::generate($data);
	}
	
	/**
	 * Retrieve key => value pairs using `id` for keys and the given attribute
	 * for values.
	 * 
	 * TODO: Filter, order, limit, offset.
	 * 
	 * @param string $attribute
	 * @return array
	 */
	public static function listing($attribute) {
		$instances = static::all();
		$list = array();
		
		foreach ($instances as $instance) {
			$list[$instance->id()] = $instance->$attribute;
		}
			
		return $list;
	}
	
	/**
	 * Save the record to storage.
	 * 
	 * TODO: $storage->error();
	 * 
	 * @return bool
	 */
	public function save() {
		if ($this->validate()) {
			$storage = $this->storage();
			$class = get_class($this);
			
			if (!$storage instanceof Modifiable) {
				throw new \Exception($class . ' storage is not modifiable');
			}
			
			$data = $this->prepareData();
			$entity = strtolower(basename($class));
			
			if (!$this->id()) {
				$id = $storage->create($this->table(), $data);
				
				if ($id) {
					$this->set($this->key(), $id);
					
					return true;
				}
				
				$this->errors['storage'] = "Failed to save $entity to storage";
				
				return false;
			} else {
				$updated = $storage->update($this->table(), $data, array($this->key() => $this->id()), 1);
				
				if ($updated) {
					return true;
				}
				
				$this->errors['storage'] = "Failed to update $entity in storage";
				
				return false;
			}
		}
		
		return false;
	}
	
	/**
	 * Save multiple record instances to storage.
	 * 
	 * Returns the number of instances that saved successfully.
	 * 
	 * @param array $instances
	 * @return int
	 */
	public static function saveMany($instances) {
		$failed = 0;
		
		foreach ($instances as $instance) {
			if (!$instance->save()) {
				$failed++;
			}
		}
		
		return count($instances) - $failed;
	}
	
	/**
	 * Delete the record from storage.
	 * 
	 * @return bool
	 */
	public function delete() {
		if ($id = $this->id()) {
			$storage = $this->storage();
			
			if ($storage instanceof Modifiable) {
				$storage->delete($this->table(), array($this->key() => $this->id()), 1);
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Determine whether the given attribute is a relation.
	 * 
	 * @param string $attribute
	 * @return bool
	 */
	protected function hasRelation($attribute) {
		$attribute = $this->prepareAttribute($attribute);
		
		return isset($this->relations[$attribute]);
	}
	
	/**
	 * Retrieve the given relation.
	 * 
	 * @param string $attribute
	 * @return \Darya\ORM\Relation
	 */
	public function relation($attribute) {
		if ($this->hasRelation($attribute)) {
			$attribute = $this->prepareAttribute($attribute);
			$relation = $this->relations[$attribute];
			
			if (!$relation instanceof Relation) {
				$type = array_shift($relation);
				$args = array_merge(array($this), $relation);
				$relation = Relation::factory($type, $args);
				$this->relations[$attribute] = $relation;
			}
			
			return $relation;
		}
		
		return null;
	}
	
	/**
	 * Determine whether the given relation has any set model(s).
	 * 
	 * @param string $attribute
	 * @return bool
	 */
	protected function hasRelated($attribute) {
		$attribute = $this->prepareAttribute($attribute);
		
		return $this->hasRelation($attribute) && $this->relation($attribute)->count();
	}
	
	/**
	 * Retrieve the model(s) of the given relation.
	 * 
	 * @param string $attribute
	 * @return array
	 */
	public function related($attribute) {
		if (!$this->hasRelation($attribute)) {
			return null;
		}
		
		$attribute = $this->prepareAttribute($attribute);
		
		$relation = $this->relation($attribute);
		
		return $relation->retrieve();
	}
	
	/**
	 * Set the given related model(s).
	 * 
	 * @param string $attribute
	 * @param mixed  $value
	 */
	protected function setRelated($attribute, $value) {
		if (!$this->hasRelation($attribute)) {
			return;
		}
		
		$relation = $this->relation($attribute);
		
		if ($value !== null && !$value instanceof $relation->model && !is_array($value)) {
			return;
		}
		
		$this->related[$this->prepareAttribute($attribute)] = $value;
	}
	
	/**
	 * Retrieve a relation. Shortcut for `relation()`.
	 * 
	 * @param string $method
	 * @param array  $arguments
	 */
	public function __call($method, $arguments) {
		return $this->relation($method);
	}
	
}
