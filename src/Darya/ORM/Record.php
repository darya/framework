<?php
namespace Darya\ORM;

use Exception;
use Darya\Storage\Query;
use Darya\Storage\Readable;
use Darya\Storage\Queryable;
use Darya\Storage\Modifiable;
use Darya\Storage\Searchable;
use Darya\Storage\Aggregational;
use Darya\Storage\Query\Builder;

/**
 * Darya's active record implementation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Record extends Model
{
	/**
	 * Database table name.
	 *
	 * Overrides the name of the database table that persists the model. The
	 * model's lower-cased class name is used if this is not set.
	 * 
	 * @var string
	 */
	protected $table;
	
	/**
	 * Instance storage.
	 * 
	 * @var Readable
	 */
	protected $storage;
	
	/**
	 * Shared storage.
	 * 
	 * @var Readable
	 */
	protected static $sharedStorage;
	
	/**
	 * Definitions of related models.
	 * 
	 * @var array
	 */
	protected $relations = array();
	
	/**
	 * Default searchable attributes.
	 * 
	 * @var array
	 */
	protected $search = array();
	
	/**
	 * Instantiate a new record with the given data or load an instance from
	 * storage if the given data is a valid primary key.
	 * 
	 * @param mixed $data An array of key-value attributes to set or a primary key to load by
	 */
	public function __construct($data = null)
	{
		if (is_numeric($data) || is_string($data)) {
			$this->data = static::load($data);
		}
		
		parent::__construct($data);
	}
	
	/**
	 * Determine whether the given attribute or relation is set on the record.
	 * 
	 * @param string $attribute
	 * @return bool
	 */
	public function has($attribute)
	{
		return $this->hasRelated($attribute) || parent::has($attribute);
	}
	
	/**
	 * Retrieve the given attribute or relation from the record.
	 * 
	 * @param string $attribute
	 * @return mixed
	 */
	public function get($attribute)
	{
		list($attribute, $subattribute) = array_pad(explode('.',  $attribute, 2), 2, null);
		
		if ($this->hasRelation($attribute)) {
			$related = $this->related($attribute);
			
			if ($related instanceof Record && $subattribute !== null) {
				return $related->get($subattribute);
			}
			
			return $related;
		}
		
		return parent::get($attribute);
	}
	
	/**
	 * Set the value of an attribute or relation on the model.
	 * 
	 * @param string $attribute
	 * @param mixed  $value
	 */
	public function set($attribute, $value = null)
	{
		if (is_string($attribute) && $this->hasRelation($attribute)) {
			$this->setRelated($attribute, $value);

			return;
		}
		
		parent::set($attribute, $value);
	}
	
	/**
	 * Unset the value for an attribute or relation on the model.
	 * 
	 * @param string $attribute
	 */
	public function remove($attribute)
	{
		if ($this->hasRelation($attribute)) {
			return $this->unsetRelated($attribute);
		}
		
		parent::remove($attribute);
	}
	
	/**
	 * Retrieve the name of the table this model belongs to.
	 * 
	 * If none is set, it defaults to creating it from the class name.
	 * 
	 * For example:
	 *     Page        -> pages
	 *     PageSection -> page_sections
	 * 
	 * @return string
	 */
	public function table()
	{
		if ($this->table) {
			return $this->table;
		}
		
		$split = explode('\\', get_class($this));
		$class = end($split);
		
		return preg_replace_callback('/([A-Z])/', function ($matches) {
			return '_' . strtolower($matches[1]);
		}, lcfirst($class)) . 's';
	}
	
	/**
	 * Get and optionally set the model's storage instance.
	 *
	 * @param Readable $storage [optional]
	 * @return Readable
	 */
	public function storage(Readable $storage = null)
	{
		$this->storage = $storage ?: $this->storage;
		
		return $this->storage ?: static::getSharedStorage();
	}
	
	/**
	 * Get the storage shared to all instances of this model.
	 * 
	 * @return Readable
	 */
	public static function getSharedStorage()
	{
		return static::$sharedStorage;
	}
	
	/**
	 * Share the given database connection to all instances of this model.
	 * 
	 * @param Readable $storage
	 */
	public static function setSharedStorage(Readable $storage)
	{
		static::$sharedStorage = $storage;
	}
	
	/**
	 * Prepare the record's data for storage. This is here until repositories
	 * are implemented.
	 * 
	 * @return array
	 */
	protected function prepareData()
	{
		$types = $this->attributes;
		
		$changed = array_intersect_key($this->data, array_flip($this->changed));
		
		$data = $this->id() ? $changed : $this->data;
		
		foreach ($data as $attribute => $value) {
			if (isset($types[$attribute])) {
				$type = $types[$attribute];
				
				switch ($type) {
					case 'int':
						$value = (int) $value;
						break;
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
	 * TODO: Filter by key if $filter has numeric keys
	 * 
	 * @param mixed $filter
	 * @return string
	 */
	protected static function prepareFilter($filter)
	{
		if ($filter === null) {
			return array();
		}
		
		if (!is_array($filter)) {
			$instance = new static;
			$filter = array($instance->key() => $filter);
		}
		
		return $filter;
	}
	
	/**
	 * Prepare the given list data.
	 * 
	 * @param array  $data
	 * @param string $attribute
	 * @return array
	 */
	protected static function prepareListing($data, $attribute)
	{
		$instance = new static;
		$key = $instance->key();
		
		$list = array();
		
		foreach ($data as $row) {
			if (isset($row[$attribute])) {
				$list[$row[$key]] = $row[$attribute];
			}
		}
		
		return $list;
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
	public static function load($filter = array(), $order = array(), $limit = 0, $offset = 0)
	{
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
	public static function find($filter = array(), $order = array())
	{
		$data = static::load($filter, $order, 1);
		
		if (empty($data[0])) {
			return false;
		}
		
		$instance = new static($data[0]);
		
		$instance->reinstate();
		
		return $instance;
	}
	
	/**
	 * Load a record instance from storage using the given criteria or create a
	 * new instance if nothing is found.
	 * 
	 * @param array|string|int $filter [optional]
	 * @param array|string     $order  [optional]
	 * @return Record
	 */
	public static function findOrNew($filter = array(), $order = array())
	{
		$instance = static::find($filter, $order);
		
		if ($instance === false) {
			return new static;
		}
		
		$instance->reinstate();
		
		return $instance;
	}

	/**
	 * Load multiple record instances matching the given IDs.
	 * 
	 * @param array|string|int $ids
	 * @return array
	 */
	public static function in($ids = array())
	{
		return static::all(['id' => (array) $ids]);
	}

	/**
	 * Load multiple record instances from storage using the given criteria.
	 * 
	 * @param array|string|int $filter [optional]
	 * @param array|string     $order  [optional]
	 * @param int              $limit  [optional]
	 * @param int              $offset [optional]
	 * @return array
	 */
	public static function all($filter = array(), $order = array(), $limit = 0, $offset = 0)
	{
		return static::hydrate(static::load($filter, $order, $limit, $offset));
	}
	
	/**
	 * Eagerly load the given relations of multiple record instances.
	 * 
	 * @param array|string     $relations
	 * @param array|string|int $filter    [optional]
	 * @param array|string     $order     [optional]
	 * @param int              $limit     [optional]
	 * @param int              $offset    [optional]
	 * @return array
	 */
	public static function eager($relations, $filter = array(), $order = array(), $limit = 0, $offset = 0)
	{
		$instance = new static;
		$instances = static::all($filter, $order, $limit, $offset);
		
		foreach ((array) $relations as $relation) {
			if ($instance->relation($relation)) {
				$instances = $instance->relation($relation)->eager($instances);
			}
		}
		
		return $instances;
	}
	
	/**
	 * Search for record instances in storage using the given criteria.
	 * 
	 * @param string           $query
	 * @param array            $attributes [optional]
	 * @param array|string|int $filter     [optional]
	 * @param array|string     $order      [optional]
	 * @param int              $limit      [optional]
	 * @param int              $offset     [optional]
	 * @return array
	 * @throws Exception
	 */
	public static function search($query, $attributes = array(), $filter = array(), $order = array(), $limit = null, $offset = 0)
	{
		$instance = new static;
		$storage = $instance->storage();
		
		if (!$storage instanceof Searchable) {
			throw new Exception(get_class($instance) . ' storage is not searchable');
		}
		
		$attributes = $attributes ?: $instance->defaultSearchAttributes();
		
		$data = $storage->search($instance->table(), $query, $attributes, $filter, $order, $limit, $offset);
		
		return static::hydrate($data);
	}
	
	/**
	 * Retrieve key => value pairs using `id` for keys and the given attribute
	 * for values.
	 * 
	 * @param string $attribute
	 * @param array  $filter    [optional]
	 * @param array  $order     [optional]
	 * @param int    $limit     [optional]
	 * @param int    $offset    [optional]
	 * @return array
	 */
	public static function listing($attribute, $filter = array(), $order = array(), $limit = null, $offset = 0)
	{
		$instance = new static;
		$storage = $instance->storage();
		
		$data = $storage->listing($instance->table(), array($instance->key(), $attribute), $filter, $order, $limit, $offset);
		
		return static::prepareListing($data, $attribute);
	}
	
	/**
	 * Retrieve the distinct values of the given attribute.
	 * 
	 * @param string $attribute
	 * @param array  $filter    [optional]
	 * @param array  $order     [optional]
	 * @param int    $limit     [optional]
	 * @param int    $offset    [optional]
	 * @return array
	 */
	public static function distinct($attribute, $filter = array(), $order = array(), $limit = null, $offset = 0)
	{
		$instance = new static;
		$storage = $instance->storage();
		
		if (!$storage instanceof Aggregational) {
			return array_values(array_unique(static::listing($attribute, $filter, $order)));
		}
		
		return $storage->distinct($instance->table(), $attribute, $filter, $order, $limit, $offset);
	}
	
	/**
	 * Create a query builder for the model.
	 * 
	 * @return Builder
	 * @throws Exception
	 */
	public static function query()
	{
		$instance = new static;
		$storage = $instance->storage();
		
		if (!$storage instanceof Queryable) {
			throw new Exception(get_class($instance) . ' storage is not queryable');
		}
		
		$query = new Query($instance->table());
		$builder = new Builder($query, $storage);
		
		$builder->callback(function ($result) use ($instance) {
			return $instance::hydrate($result->data);
		});
		
		return $builder;
	}
	
	/**
	 * Create the model in storage.
	 * 
	 * @return bool
	 */
	protected function saveNew()
	{
		$data = $this->prepareData();
		$storage = $this->storage();
		
		// Create a new item
		$id = $storage->create($this->table(), $data);
		
		// Bail if saving failed
		if (!$id) {
			return false;
		}
		
		// If we didn't get a boolean back, assume this is an ID (TODO: Formalise)
		if (!is_bool($id)) {
			$this->set($this->key(), $id);
		}
		
		$this->reinstate();
		
		return true;
	}
	
	/**
	 * Update the model in storage.
	 * 
	 * @return bool
	 */
	protected function saveExisting()
	{
		$data = $this->prepareData();
		$storage = $this->storage();
		
		// We can bail if there isn't any new data to save
		if (empty($data)) {
			return true;
		}
		
		// Attempt to update an existing item
		$updated = $storage->update($this->table(), $data, array($this->key() => $this->id()), 1);
		
		// Otherwise it doesn't exist, so we can attempt to create it
		// TODO: Query result error check
		if (!$updated) {
			$updated = $storage->create($this->table(), $data) > 0;
		}
		
		return $updated;
	}
	
	/**
	 * Save the record to storage.
	 * 
	 * @param array $options [optional]
	 * @return bool
	 * @throws Exception
	 */
	public function save(array $options = array())
	{
		// Bail if the model is not valid
		if (!$this->validate()) {
			return false;
		}
		
		$storage = $this->storage();
		$class = get_class($this);
		
		// Storage must be modifiable in order to save
		if (!$storage instanceof Modifiable) {
			throw new Exception($class . ' storage is not modifiable');
		}
		
		// Create or update the model in storage
		if (!$this->id()) {
			$success = $this->saveNew();
		} else {
			$success = $this->saveExisting();
		}
		
		if ($success) {
			// Clear the changed attributes; we're in sync now
			$this->reinstate();
			
			// Save relations if we don't want to skip
			if (empty($options['skipRelations'])) {
				$this->saveRelations();
			}
		} else {
			$this->errors['save'] = "Failed to save $class instance";
			$this->errors['storage'] = $this->storage()->error();
		}
		
		return $success;
	}
	
	/**
	 * Save multiple record instances to storage.
	 * 
	 * Returns the number of instances that saved successfully.
	 * 
	 * @param array $instances
	 * @param array $options   [optional]
	 * @return int
	 */
	public static function saveMany($instances, array $options = array())
	{
		$saved = 0;
		
		foreach ($instances as $instance) {
			if ($instance->save($options)) {
				$saved++;
			}
		}
		
		return $saved;
	}
	
	/**
	 * Delete the record from storage.
	 * 
	 * @return bool
	 */
	public function delete()
	{
		$storage = $this->storage();
		
		if (!$this->id() || !($storage instanceof Modifiable)) {
			return false;
		}
		
		return (bool) $storage->delete($this->table(), array($this->key() => $this->id()), 1);
	}
	
	/**
	 * Retrieve the list of relation attributes for this model.
	 * 
	 * @return array
	 */
	public function relationAttributes()
	{
		return array_keys($this->relations);
	}
	
	/**
	 * Determine whether the given attribute is a relation.
	 * 
	 * @param string $attribute
	 * @return bool
	 */
	protected function hasRelation($attribute)
	{
		$attribute = $this->prepareAttribute($attribute);
		
		return isset($this->relations[$attribute]);
	}
	
	/**
	 * Retrieve the given relation.
	 * 
	 * @param string $attribute
	 * @return Relation
	 */
	public function relation($attribute)
	{
		if (!$this->hasRelation($attribute)) {
			return null;
		}
		
		$attribute = $this->prepareAttribute($attribute);
		$relation = $this->relations[$attribute];
		
		if (!$relation instanceof Relation) {
			$type = array_shift($relation);
			$arguments = array_merge(array($this), $relation);
			$arguments['name'] = $attribute;
			
			$relation = Relation::factory($type, $arguments);
			
			$this->relations[$attribute] = $relation;
		}
		
		return $relation;
	}
	
	/**
	 * Retrieve all relations.
	 * 
	 * @return Relation[]
	 */
	public function relations()
	{
		$relations = array();
		
		foreach ($this->relationAttributes() as $attribute) {
			$relations[$attribute] = $this->relation($attribute);
		}
		
		return $relations;
	}
	
	/**
	 * Determine whether the given relation has any set models.
	 * 
	 * @param string $attribute
	 * @return bool
	 */
	protected function hasRelated($attribute)
	{
		$attribute = $this->prepareAttribute($attribute);
		
		return $this->hasRelation($attribute) && $this->relation($attribute)->count();
	}
	
	/**
	 * Retrieve the models of the given relation.
	 * 
	 * @param string $attribute
	 * @return array
	 */
	public function related($attribute)
	{
		if (!$this->hasRelation($attribute)) {
			return null;
		}
		
		$attribute = $this->prepareAttribute($attribute);
		
		$relation = $this->relation($attribute);
		
		return $relation->retrieve();
	}
	
	/**
	 * Set the given related models.
	 * 
	 * @param string $attribute
	 * @param mixed  $value
	 */
	protected function setRelated($attribute, $value)
	{
		if (!$this->hasRelation($attribute)) {
			return;
		}
		
		$relation = $this->relation($attribute);
		
		$relation->detach();
		
		if ($value === null) {
			return;
		}
		
		$relation->attach($value);
	}

	/**
	 * Unset the models of the given relation.
	 * 
	 * @param string $attribute
	 */
	protected function unsetRelated($attribute)
	{
		if (!$this->hasRelation($attribute)) {
			return;
		}
		
		$this->relation($attribute)->detach();
	}

	/**
	 * Save all of the model's relations.
	 */
	public function saveRelations()
	{
		foreach ($this->relations() as $relation) {
			$relation->save();
		}
	}

	/**
	 * Retrieve the default search attributes for the model.
	 * 
	 * @return array
	 */
	public function defaultSearchAttributes()
	{
		return $this->search;
	}
	
	/**
	 * Retrieve a relation. Shortcut for `relation()`.
	 * 
	 * @param string $method
	 * @param array  $arguments
	 * @return Relation
	 * @throws Exception
	 */
	public function __call($method, $arguments)
	{
		if ($this->hasRelation($method)) {
			return $this->relation($method);
		}
		
		throw new Exception('Call to undefined method ' . get_class($this) . '::' . $method . '()');
	}
}
