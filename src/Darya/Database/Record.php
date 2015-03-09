<?php
namespace Darya\Database;

use Darya\Common\Tools;
use Darya\Database\DatabaseInterface;
use Darya\Mvc\Model;

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
	 * Override for the name of the database table that persists the model. If
	 * this is not set, the model's lowercased class name is used.
	 * 
	 * @var string Database table name
	 */
	protected $table;
	
	/**
	 * @var \Darya\Database\DatabaseInterface Instance database connection
	 */
	protected $connection;
	
	/**
	 * @var \Darya\Database\DatabaseInterface Shared database connection
	 */
	protected static $sharedConnection;
	
	/**
	 * @var string Prefix for record attributes when saving
	 */
	protected $prefix;
	
	/**
	 * @var array Attributes that should never be prefixed
	 */
	protected $prefixless = array();
	
	/**
	 * @var bool Whether to use the record's class name to prefix attributes if
	 *           an explicit prefix is not set
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
	 * Retreive the model's database connection.
	 * 
	 * @return \Darya\Database\DatabaseInterface The connection assigned to this model instance, or the models static connection
	 */
	public function connection(DatabaseInterface $connection = null) {
		$this->connection = $connection ?: $this->connection;
		
		return isset($this->connection) ? $this->connection : static::getSharedConnection();
	}
	
	/**
	 * Get the database connection shared to all instances of this model.
	 * 
	 * @return \Darya\Database\DatabaseInterface
	 */
	public static function getSharedConnection() {
		return static::$sharedConnection;
	}
	
	/**
	 * Share the given database connection to all instances of this model.
	 * 
	 * @param \Darya\Database\DatabaseInterface $connection
	 */
	public static function setSharedConnection(DatabaseInterface $connection) {
		static::$sharedConnection = $connection;
	}
	
	/**
	 * Retrieve the prefix for this model's attributes.
	 * 
	 * @return string
	 */
	public function prefix() {
		if ($this->prefix !== null) {
			return strtolower($this->prefix);
		}
		
		if ($this->classPrefix) {
			return Tools::camelToDelim(static::basename(), '_') . '_';
		}
		
		return '';
	}
	
	/**
	 * Determine whether the given attribute should not be prefixed.
	 * 
	 * @param string $attribute
	 * @return bool
	 */
	protected function prefixless($attribute) {
		return in_array($attribute, $this->prefixless);
	}
	
	/**
	 * Prefix the given attribute name if needed.
	 * 
	 * @param string $attribute
	 * @return string
	 */
	public function prefixAttribute($attribute) {
		$attribute = $this->prepareAttribute($attribute);
		
		if (strlen($this->prefix()) && !$this->prefixless($attribute) && strpos($attribute, $this->prefix()) !== 0) {
			$attribute = $this->prefix() . $attribute;
		}
		
		return $attribute;
	}
	
	public function unprefixAttribute($attribute) {
		$attribute = $this->prepareAttribute($attribute);
		
		if (strlen($this->prefix()) && strpos($attribute, $this->prefix()) === 0) {
			return substr($attribute, strlen($this->prefix()));
		}
		
		return $attribute;
	}
	
	public function unprefixLoadedData($data) {
		foreach ($data as $i => $instance) {
			foreach ($instance as $key => $value) {
				// $instance[$]
			}
		}
	}
	
	protected function prefixedAttributes() {
		if (!$this->prefixedAttributes) {
			foreach ($this->attributes as $key => $value) {
				$this->prefixedAttributes[$this->prefixAttribute($key)] = $value;
			}
		}
		
		return $this->prefixedAttributes;
	}
	
	protected function prepareMutations() {
		$mutations = array();
		
		foreach ($this->mutations as $key => $value) {
			$mutations[$this->prefixAttribute($key)] = $value;
		}
		
		return $mutations;
	}
	
	/**
	 * Prepare the record's data for saving to the database. This is here
	 * until repositories are implemented.
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
	protected static function prepareFilters($filters, $operator = 'AND', $excludeWhere = false) {
		$instance = new static;
		$connection = $instance->connection();
		$where = '';
		
		if (is_array($filters)) { // By condition array
			if (count($filters) > 0) {
				$conditions = array();
				
				foreach ($filters as $k => $v) {
					$field = $connection->escape($instance->prefixAttribute($k));
					$op = !$connection->endsWithOperator($field) ? '=' : '';
					
					if (is_array($v)) {
						$v = array_map(array($connection, 'escape'), $v);
						$value = "('" . implode("','", $v) . "')";
					} else {
						$value = "'".$connection->escape($v)."'";
					}
					
					$conditions[] = "$field $op $value";
				}
				
				$where = (!$excludeWhere ? ' WHERE ' : '') . implode(" $operator ", $conditions);
			}
		} else { // By key
			if (!empty($filters)) {
				$filters = $connection->escape($filters);
				$where = (!$excludeWhere ? ' WHERE ' : '') . $instance->key() . " = '$filters'";
			}
		}
		
		return $where;
	}
	
	/**
	 * Processes orders array into an ORDER BY statement
	 * 
	 * Order array elements can:
	 * Have column names as keys and [ASC|DESC] as values
	 * Have column names as values, where ASC is assumed
	 * 
	 * @param array|string $orders
	 * @return string
	 */
	protected static function prepareOrders($orders = array()){
		$connection = static::getSharedConnection();
		$orderby = '';
		
		if (is_array($orders)) {
			if (count($orders) > 0) {
				$conditions = array();
				
				foreach ($orders as $k => $v) {
					$v = $connection->escape($this->prefixAttribute($v));
					
					if (is_numeric($k)) { // Value is field, assume ascending
						$conditions[] = $v . ' ASC';
					} else { // Key is field, use value as order, fall back to ascending
						$conditions[] = $connection->escape($k) . ' ' . ($v ? $v : 'ASC');
					}
				}
				
				$orderby = ' ORDER BY ' . implode(', ', $conditions);
			}
		} else {
			if (is_string($orders)) {
				$orderby = ' ORDER BY ' . $connection->escape($orders);
			}
		}
		
		return $orderby;
	}
	
	/**
	 * Load the data of a Record instance without instantiating it
	 * 
	 * @param mixed $filters
	 * @param array|string $orders
	 * @return array
	 */
	public static function loadData($filters = array(), $orders = array()){
		$instance = new static;
		$connection = $instance->connection();
		$where = static::prepareFilters($filters);
		$orderby = static::prepareOrders($orders);
		$data = $connection->query("SELECT * FROM " . $instance->table() . $where . $orderby);
		return $data;
	}
	
	/**
	 * Loads a new instance of the Record from the database
	 * Returns false if the Record cannot be found in the database
	 * 
	 * @static
	 * @param mixed $filters
	 * @param array|string $orders
	 * @return Record|bool
	 */
	public static function load($filters = array(), $orders = array()) {
		$data = static::loadData($filters, $orders);
		
		return $data && isset($data[0]) ? new static($data[0]) : false;
	}
	
	/**
	 * Loads a new instance of the record from the database.
	 * 
	 * Returns a new instance if the record cannot be found.
	 * 
	 * @static
	 * @param mixed $filters
	 * @param array|string $orders
	 * @return Record
	 */
	public static function loadOrNew($filters = array(), $orders = array()) {
		$data = static::loadData($filters, $orders);
		
		return $data && isset($data[0]) ? new static($data[0]) : new static;
	}
	
	/**
	 * Load the data of multiple Records from the database without instantiating them
	 * 
	 * @static
	 * @param mixed $filters
	 * @param array|string $orders
	 * @return array
	 */
	public static function loadAllData($filters = array(), $orders = array()) {
		$instance = new static;
		$connection = $instance->connection();
		$where = static::prepareFilters($filters);
		$orderby = static::prepareOrders($orders);
		return $connection->query("SELECT * FROM " . $instance->table() . $where . $orderby);
	}
	
	/**
	 * Load multiple Record instances from the database.
	 * 
	 * @static
	 * @param mixed $filters
	 * @param array|string $orders
	 * @return array
	 */
	public static function loadAll($filters = array(), $orders = array()) {
		return static::output(static::loadAllData($filters, $orders));
	}
	
	/**
	 * Alias for loadAll
	 * 
	 * @static
	 * @param mixed $filters
	 * @param array|string $orders
	 * @return array
	 */
	public static function all($filters = array(), $orders = array()) {
		return static::loadAll($filters, $orders);
	}
	
	public static function search($query, $fields = array(), $filters = array(), $orders = array()) {
		$instance = new static;
		$connection = $instance->connection();
		
		$fieldFilters = array();
		
		foreach ($fields as $field) {
			$fieldFilters[$field . ' LIKE'] = "%$query%";
		}
		
		$filterGroups = array(' WHERE (' . static::prepareFilters($fieldFilters, 'OR', true) . ')');
		
		if ($filters) {
			$filterGroups[] = static::prepareFilters($filters, null, true);
		}
		
		$where = implode(' AND ', $filterGroups);
		$orderby = static::prepareOrders($orders);
		return static::output($connection->query('SELECT * FROM ' . $instance->table() . $where . $orderby));
	}
	
	/**
	 * Return an array of key => value pairs using id for keys
	 * and the given field for values.
	 * 
	 * @param string $field
	 * @return array
	 */
	public static function loadList($field) {
		$instances = static::loadAll();
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
