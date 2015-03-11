<?php
namespace Darya\Database;

use Darya\Common\Tools;
use Darya\Database\DatabaseInterface;
use Darya\Mvc\Model;
use Darya\Storage\Modifiable;

/**
 * Darya's active record implementation for models.
 * 
 * TODO: SQL doesn't belong here. Implement Query objects, a QueryBuilder,
 * and/or implement generic repositories.
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
	 * @var string Prefix for record attributes when interacting with storage
	 */
	protected $prefix;
	
	/**
	 * Attributes that should never be prefixed when interacting with storage.
	 * 
	 * @var array
	 */
	protected $prefixless = array();
	
	/**
	 * Whether to use the record's class name to prefix attributes if an
	 * explicit prefix is not set.
	 * 
	 * @var bool
	 */
	protected $classPrefix = false;
	
	/**
	 * @var array Prefixed attributes cache
	 */
	protected $prefixedAttributes = array();
	
	/**
	 * Instantiate a new record with the given data or load an instance from the
	 * database if the given data is a valid primary key.
	 * 
	 * @param mixed $data An array of key-value attributes to set or a primary key to load by
	 */
	public function __construct($data = null) {
		if (is_array($data)) {
			$this->set($data);
		} else if(is_numeric($data) || is_string($data)) {
			$this->data = static::loadData($data);
		}
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
		return $this->table ? $this->table : Tools::camelToDelim(static::basename(), '_').'s';
	}
	
	/**
	 * Get and optionally set the model's storage instance.
	 * 
	 * @return \Darya\Storage\Readable
	 */
	public function storage(Readable $storage = null) {
		$this->storage = $storage ?: $this->storage;
		
		return isset($this->storage) ? $this->storage : static::getSharedStorage();
	}
	
	/**
	 * Get the storage shared to all instances of this model.
	 * 
	 * @return \Darya\Storage\Readable
	 */
	public static function getSharedStorage() {
		return static::$sharedConnection;
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
		$mutations = $this->prepareMutations();
		
		$data = array_intersect_key($this->data, $this->attributes) ?: $this->data;
		
		foreach ($data as $key => $value) {
			if (isset($mutations[$key])) {
				$mutation = $mutations[$key];
				
				switch ($mutation) {
					case 'date':
						$value = date('Y-m-d', $value);
						break;
					case 'datetime':
						vard($value);
						$value = date('Y-m-d H:i:s', $value);
						break;
					case 'time':
						$value = date('H:i:s', $value);
						break;
				}
				
				$data[$key] = $value;
			}
		}
		
		return $data;
	}
	
	/**
	 * Prepare the given filters array or primary key as a WHERE statement.
	 * 
	 * @param mixed  $filters
	 * @param string $operator
	 * @param bool   $excludeWhere
	 * @return string
	 */
	protected static function prepareFilter($filter) {
		$instance = new static;
		
		if (!is_array($filter)) {
			$filter = array($instance->key() => $filter);
		}
		
		return $filter;
	}
	
	/**
	 * Load the record data from storage using the given criteria.
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
		return static::output(static::load($filter, $order, $limit, $offset));
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
		$storage = $instance->connection();
		
		if (!$storage instanceof Darya\Storage\Searchable) {
			throw new \Exception(get_class($instance) . ' storage is not searchable');
		}
		
		$data = $storage->search($query, $attributes, $filter, $order, $limit, $offset);
		
		return static::output($data);
	}
	
	/**
	 * Return an array of key => value pairs using id for keys
	 * and the given field for values.
	 * 
	 * @param string $field
	 * @return array
	 */
	public static function listing($field) {
		$instances = static::all();
		$list = array();
		
		foreach ($instances as $instance) {
			$list[$instance->id] = $instance->$field;
		}
			
		return $list;
	}
	
	/**
	 * Save the record to the database.
	 * 
	 * @return bool
	 */
	public function save() {
		if ($this->validate()) {
			$connection = $this->connection();
			$data = $this->prepareData();
			$keys = array_keys($data);
			
			// Escape values
			$data = array_map(function($value) use ($connection) {
				return $connection->escape($value);
			}, $data);
			
			if (!$this->id()) {
				$q = $connection->query("INSERT INTO " . $this->table() . " (" . implode(',', $keys) . ") VALUES ('" . implode("','", $data) . "')", true);
				
				if (!$connection->error()) {
					$this->set($this->key(), $q['insert_id']);
					return $q['insert_id'];
				}
				
				$this->errors['database'] = $connection->error();
				
				return false;
			} else {
				$update = array();
				
				foreach ($data as $key => $value) {
					$update[$key] = "$key = '$value'";
				}
				
				$q = $connection->query("UPDATE " . $this->table() . " SET " . implode(', ', $update) . " WHERE " . $this->key() . " = '{$this->id()}'");
				
				if ($connection->error()) {
					$this->errors['database'] = $connection->error();
				}
				
				return !$connection->error();
			}
		}
		
		return false;
	}
	
	/**
	 * Save multiple record instances to the database.
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
	 * Delete the record from the database.
	 * 
	 * @return bool
	 */
	public function delete() {
		if ($id = $this->id()) {
			$connection = $this->connection();
			
			$connection->query("DELETE FROM " . $this->table() . " WHERE " . $this->key() . " = '$id' LIMIT 1");
			
			if ($connection->error()) {
				$this->errors['database'] = $connection->error();
			}
			
			return !$connection->error();
		}
		
		return false;
	}
	
	/**
	 * Load a set of $class records related to $this record.
	 * 
	 * If an array of classes is provided, the last in the list is what will be
	 * instantiated (related through the others).
	 * 
	 * @param mixed $class   Single Record class as a string or multiple related Record classes
	 * @param array $filters Set of filters for the query
	 * @param array $orders  Set of ordering rules for the query
	 * @return array
	 */
	public function loadRelated($class, $filters = array(), $orders = array()) {
		$instances = array();
		if (!is_array($class)) {
			if (class_exists($class)) {
				$filters = array_merge(array($this->key() => $this->id()), $filters);
				$instances = $class::loadAll($filters, $orders);
			}
		} else {
			if (count($class) > 1) {
				$filters = array_merge(array($this->key() => $this->id()), $filters);
				$where = static::prepareFilters($filters);
				$orderby = static::prepareOrders($orders);
				
				$joins = array();
				for ($i = 0; $i < count($class) - 1; $i++) {
					$c = $class[$i];
					$d = $class[$i+1];
					if (class_exists($c) && class_exists($d)) {
						$ci = new $c;
						$di = new $d;
						$joins[] = ' INNER JOIN ' . $ci->table() . ' USING (' . $di->key() . ')';
					}
				}
				
				$lastClass = $class[count($class)-1];
				$lastInstance = new $lastClass;
				
				$connection = $this->connection();
				
				$data = $connection->query(
					'SELECT ' . $lastInstance->table() . '.* FROM '.$lastInstance->table()
					. implode(' AND ',$joins)
					. $where . $orderby
				);
				
				if ($connection->error()) {
					$this->errors['database'] = $connection->error();
				}
				
				$instances = $lastClass::output($data);
			} else {
				$instances = $this->loadRelated($class[0]);
			}
		}
		
		return $instances;
	}
	
	/**
	 * Save records in relation to $this
	 *
	 * @param string $rClass    Relation class
	 * @param string $mClass    Model class
	 * @param array  $relations Instances of records to relate to $this using relation class
	 * @return bool
	 */
	public function saveRelated($rClass, $mClass, array $relations) {
		if ($this->id() && count($relations) && class_exists($rClass) && class_exists($mClass)) {
			$relationQueries = array();
			
			foreach ($relations as $key => $model) {
				if (get_class($model) == $mClass) {
					if (!$model->id) {
						$model->save();
					}
					
					if ($model->id) {
						$relation = new $rClass();
						
						if ($relation->key()) { // Simple key (single field)
							$relation = $rClass::loadOrNew(array(
								$this->key() => $this->id,
								$model->key() => $model->id
							));
							
							if ($relation) {
								$relationQueries[] = "('" . implode("','", array($relation->id, $this->id, $model->id)) . "')";
							}
						} else { // Compound key
							$relationQueries[] = "('" . implode("','", array($this->id, $model->id)) . "')";
						}
					}
				}
			}
			
			if (count($relationQueries)) {
				$connection = $this->connection();
				$relation = new $rClass();
				$model = new $mClass();
				
				$connection->query(
					'REPLACE INTO ' . $relation->table()
					. ' (' . ($relation->key() ? $relation->key() . ',' : '') . $this->key() . ',' . $model->key() . ')'
					. ' VALUES ' . implode(', ', $relationQueries)
				);
				
				if ($connection->error()) {
					$this->errors['database'] = $connection->error();
				}
				
				return !$connection->error();
			}
		}
		
		return false;
	}
	
	/**
	 * Delete records related to $this
	 *
	 * @param string $rClass    Relation class
	 * @param string $mClass    Model class
	 * @param Array  $relations Instances of records to delete in relation to $this 
	 */
	public function deleteRelated($rClass, $mClass, $relations) {
		if ($this->id() && !empty($relations) && class_exists($rClass) && class_exists($mClass)) {
			$deleteQueries = array();
			
			foreach ($relations as $key => $model) {
				if (get_class($model) == $mClass) {
					if ($model->id) {
						$relation = new $rClass();
						
						if ($relation->key()) {
							$relation = $rClass::load(array(
								$this->key() => $this->id,
								$model->key() => $model->id
							));
							
							if ($relation) {
								$deleteQueries[] = $relation->key() . "='" . $relation->id . "'";
							}
						} else { // Compound key
							$deleteQueries[] = $model->key() . "='" . $model->id . "'";
						}
					}
				}
			}
			
			$relation = new $rClass();
			$this->connection()->query(
				'DELETE FROM ' . $relation->table()
				.' WHERE ' . $this->key() . "='" . $this->id . "' AND (" . implode(' OR ', $deleteQueries) . ')'
			);
			
			return !$this->connection()->error();
		}
		
		return false;
	}
	
}