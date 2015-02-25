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
	 * this is not set, the model's class name in lower case is used.
	 * 
	 * @var string Database table name
	 */
	protected $table;
	
	/**
	 * @var Darya\Database\DatabaseInterface Instance database connection
	 */
	protected $connection;
	
	/**
	 * @var Darya\Database\DatabaseInterface Shared database connection
	 */
	protected static $defaultConnection;
	
	/**
	 * Instantiate a new record with the given data or load an instance from the
	 * database if the given data is a valid primary key.
	 * 
	 * @param mixed $data An array of key-value fields or a primary key to load by
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
	 * If none is set, it defaults to creating it from the class name.
	 * For example:
	 *     Page -> pages
	 *     PageSection -> page_sections
	 */
	public function getTable() {
		return $this->table ? $this->table : Tools::camelToDelim(static::basename(), '_').'s';
	}
	
	/**
	 * Returns a database connection for the model
	 * 
	 * @return Database The connection assigned to this model instance, or the models static connection
	 */
	public function getConnection() {
		return isset($this->connection) ? $this->connection : static::getDefaultConnection();
	}
	
	/**
	 * Set the database connection to use for the model instance
	 * 
	 * @param Darya\Database\DatabaseInterface $connection
	 */
	public function setConnection(DatabaseInterface $connection) {
		$this->connection = $connection;
	}
	
	/**
	 * Get the default database connection to use for instances of this model
	 * 
	 * @return Darya\Database\DatabaseInterface
	 */
	public static function getDefaultConnection() {
		return static::$defaultConnection;
	}
	
	/**
	 * Set the default database connection to use instances of this model
	 * 
	 * @param Darya\Database\DatabaseInterface $connection
	 */
	public static function setDefaultConnection(DatabaseInterface $connection) {
		static::$defaultConnection = $connection;
	}
	
	/**
	 * Processes filters array or primary key into a WHERE statement 
	 *
	 * @static
	 * @param mixed  $filters
	 * @param string $operator
	 * @param bool   $excludeWhere
	 * @return string
	 */
	protected static function processFilters($filters, $operator = 'AND', $excludeWhere = false) {
		$instance = new static();
		$connection = $instance->getConnection();
		$where = '';
		
		if (is_array($filters)) { // By condition array
			if (count($filters) > 0) {
				$conditions = array();
				
				foreach ($filters as $k => $v) {
					$field = $connection->escape($k);
					$op = !$connection->endsWithOperator($field) ? '=' : '';
					
					if (is_array($v)) {
					    $v = array_map(array($connection, 'escape'), $v);
					    $value = "('" . implode("','", $v) . "')";
					} else {
					    $value = "'".$connection->escape($v)."'";
					}
					
					$conditions[] = "$field $op $value";
				}
				
				$where = (!$excludeWhere?' WHERE ':'') . implode(" $operator ", $conditions);
			}
		} else { // By key
			if (!empty($filters)) {
				$filters = $connection->escape($filters);
				$where .= (!$excludeWhere?' WHERE ':'') . $instance->getKey() . " = '$filters'";
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
	 * @static
	 * @param array|string $orders
	 * @return string
	 */
	protected static function processOrders($orders = array()){
		$connection = static::getDefaultConnection();
		$orderby = '';
		
		if (is_array($orders)) {
			if (count($orders) > 0) {
				$conditions = array();
				
				foreach ($orders as $k => $v) {
					$v = $connection->escape($v);
					
					if (is_numeric($k)) { // Value is field, assume ascending
						$conditions[] = $v . ' ASC';
					} else { // Key is field, use value as order
						$conditions[] = $connection->escape($k).' '.($v ? $v : 'ASC');
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
		$connection = $instance->getConnection();
		$where = static::processFilters($filters);
		$orderby = static::processOrders($orders);
		$data = $connection->query("SELECT * FROM " . $instance->getTable() . $where . $orderby);
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
		return $data ? new static($data[0]) : false;
	}
	
	/**
	 * Loads a new instance of the Record from the database
	 * Returns a new Record instance if the Record cannot be found in the database
	 * 
	 * @static
	 * @param mixed $filters
	 * @param array|string $orders
	 * @return Record
	 */
	public static function loadOrNew($filters = array(), $orders = array()) {
		$data = static::loadData($filters, $orders);
		return $data && isset($data[0]) ? new static($data[0]) : new static(); // from qst. good/bad idea?
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
		$connection = $instance->getConnection();
		$where = static::processFilters($filters);
		$orderby = static::processOrders($orders);
		return $connection->query("SELECT * FROM " . $instance->getTable() . $where . $orderby);
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
	
	/**
	 * Save multiple Record instances to the database.
	 * 
	 * @return int Number of instances that saved successfully
	 */
	public static function saveAll($instances) {
		$failed = 0;
		
		foreach ($instances as $instance) {
			if (!$instance->save()) {
				$failed++;
			}
		}
		
		return count($instances) - $failed;
	}
	
	public static function search($query, $fields = array(), $filters = array(), $orders = array()) {
		$instance = new static;
		$connection = $instance->getConnection();
		
		$fieldFilters = array();
		
		foreach ($fields as $field) {
			$fieldFilters[$field.' LIKE'] = "%$query%";
		}
		
		$filterGroups = array(' WHERE (' . static::processFilters($fieldFilters, 'OR', true) . ')');
		
		if ($filters) {
			$filterGroups[] = static::processFilters($filters, null, true);
		}
		
		$where = implode(' AND ', $filterGroups);
		$orderby = static::processOrders($orders);
		return static::output($connection->query('SELECT * FROM ' . $instance->getTable() . $where . $orderby));
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
			$connection = $this->getConnection();
			$data = $this->data;
			$keys = array_keys($this->data);
			
			// Escape values
			$data = array_map(function($value) use ($connection) {
				return $connection->escape($value);
			}, $data);
			
			if (!$this->getId()) {
				$q = $connection->query("INSERT INTO " . $this->getTable() . " (" . implode(',', $keys) . ") VALUES ('" . implode("','", $data) . "')", true);
				
				if (!$connection->error()) {
					$this->set($this->getKey(), $q['insert_id']);
					return $q['insert_id'];
				}
				
				if ($connection->error()) {
					$this->errors['database'] = $connection->error();
				}
				
				return false;
			} else {
				$update = array();
				
				foreach ($data as $key => $value) {
					$update[$key] = "$key = '$value'";
				}
				
				$q = $connection->query("UPDATE " . $this->getTable() . " SET " . implode(', ', $update) . " WHERE " . $this->getKey() . " = '{$this->getId()}'");
				
				if ($connection->error()) {
					$this->errors['database'] = $connection->error();
				}
				
				return !$connection->error();
			}
		}
		
		return false;
	}
	
	/**
	 * Delete the record from the database.
	 * 
	 * @return bool
	 */
	public function delete() {
		if ($id = $this->getId()) {
			$connection = $this->getConnection();
			
			$connection->query("DELETE FROM " . $this->getTable() . " WHERE " . $this->getKey() . " = '$id' LIMIT 1");
			
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
				$filters = array_merge(array($this->getKey() => $this->getId()), $filters);
				$instances = $class::loadAll($filters, $orders);
			}
		} else {
			if (count($class) > 1) {
				$filters = array_merge(array($this->getKey() => $this->getId()), $filters);
				$where = static::processFilters($filters);
				$orderby = static::processOrders($orders);
                
				$joins = array();
				for ($i = 0; $i < count($class) - 1; $i++) {
					$c = $class[$i];
					$d = $class[$i+1];
					if (class_exists($c) && class_exists($d)) {
						$ci = new $c;
						$di = new $d;
						$joins[] = ' INNER JOIN ' . $ci->getTable() . ' USING (' . $di->getKey() . ')';
					}
				}
				
				$lastClass = $class[count($class)-1];
				$lastInstance = new $lastClass;
				
				$connection = $this->getConnection();
				
				$data = $connection->query(
					'SELECT ' . $lastInstance->getTable() . '.* FROM '.$lastInstance->getTable()
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
		if ($this->getId() && count($relations) && class_exists($rClass) && class_exists($mClass)) {
			$relationQueries = array();
			
			foreach ($relations as $key => $model) {
				if (get_class($model) == $mClass) {
					if (!$model->id) {
						$model->save();
					}
					
					if ($model->id) {
						$relation = new $rClass();
						
						if ($relation->getKey()) { // Simple key (single field)
							$relation = $rClass::loadOrNew(array(
								$this->getKey() => $this->id,
								$model->getKey() => $model->id
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
				$connection = $this->getConnection();
				$relation = new $rClass();
				$model = new $mClass();
				
				$connection->query(
					'REPLACE INTO ' . $relation->getTable()
					. ' (' . ($relation->getKey() ? $relation->getKey() . ',' : '') . $this->getKey() . ',' . $model->getKey() . ')'
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
		if ($this->getId() && !empty($relations) && class_exists($rClass) && class_exists($mClass)) {
			$deleteQueries = array();
			
			foreach ($relations as $key => $model) {
				if (get_class($model) == $mClass) {
					if ($model->id) {
						$relation = new $rClass();
						
						if ($relation->getKey()) {
							$relation = $rClass::load(array(
								$this->getKey() => $this->id,
								$model->getKey() => $model->id
							));
							
							if ($relation) {
								$deleteQueries[] = $relation->getKey() . "='" . $relation->id . "'";
							}
						} else { // Compound key
							$deleteQueries[] = $model->getKey() . "='" . $model->id . "'";
						}
					}
				}
			}
			
			$relation = new $rClass();
			$this->getConnection()->query(
				'DELETE FROM ' . $relation->getTable()
				.' WHERE ' . $this->getKey() . "='" . $this->id . "' AND (" . implode(' OR ', $deleteQueries) . ')'
			);
			
			return !$this->getConnection()->error();
		}
		
		return false;
	}
	
}
