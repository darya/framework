<?php
namespace Darya\Database\Query\Translator;

use Darya\Database;
use Darya\Database\Query\Translator;
use Darya\Storage;

/**
 * Darya's MySQL query translator.
 * 
 * TODO: Separate the switch statement bodies out into their own methods.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class MySql implements Translator {
	
	/**
	 * @var Database\Connection
	 */
	protected $connection;
	
	/**
	 * Instantiate a new MySQL query translator.
	 * 
	 * @param Database\Connection $connection
	 */
	public function __construct(Database\Connection $connection) {
		$this->connection = $connection;
	}
	
	/**
	 * Translate the given storage query into a MySQL query.
	 * 
	 * @param Storage\Query $storageQuery
	 * @return Database\Query
	 */
	public function translate(Storage\Query $storageQuery) {
		$type = $storageQuery->type;
		
		switch ($type) {
			case Storage\Query::CREATE:
				$query = new Query(
					$this->prepareInsert($storageQuery->resource, $storageQuery->data)
				);
				break;
			case Storage\Query::READ:
				$query = new Database\Query(
					$this->prepareSelect($storageQuery->resource, '*',
						$this->prepareWhere($storageQuery->filter),
						$this->prepareOrderBy($storageQuery->order),
						$this->prepareLimit($storageQuery->limit, $storageQuery->offset)
					)
				);
				break;
			case Storage\Query::UPDATE:
				$query = new Database\Query(
					$this->prepareUpdate($storageQuery->resource, $data,
						$this->prepareWhere($storageQuery->filter),
						$this->prepareLimit($storageQuery->limit, $storageQuery->offset)
					)
				);
				break;
			case Storage\Query::DELETE:
				$query = new Database\Query(
					$this->prepareDelete($storageQuery->resource,
						$this->prepareWhere($storageQuery->filter),
						$this->prepareLimit($storageQuery->limit, $storageQuery->offset)
					)
				);
				break;
		}
		
		return isset($query) ? $query : new Database\Query;
	}
	
	/**
	 * Escape the given value.
	 * 
	 * If the value is an array, it is recursively escaped.
	 * 
	 * @param array|string $value
	 * @return array
	 */
	protected function escape($value) {
		if (is_array($value)) {
			return array_map(array($this, 'escape'), $value);
		} else if (!is_object($value)) {
			return $this->connection->escape($value);
		}
		
		return $value;
	}
	
	/**
	 * Prepare the given columns as a string.
	 * 
	 * @param array|string $columns
	 * @return string
	 */
	protected function prepareColumns($columns) {
		if (!is_array($columns)) {
			return (string) $columns;
		}
		
		$columns = $this->escape($columns);
		
		return implode(', ', $columns);
	}
	
	/**
	 * Prepare an individual filter condition.
	 * 
	 * @param string       $column
	 * @param array|string $value
	 * @return string
	 */
	protected function prepareFilter($column, $value) {
		list($column, $operator) = array_pad(explode(' ', $column, 2), 2, null);
		$column = $this->escape($column);
		$operator = in_array(strtolower($operator), $this->operators) ? $this->escape($operator) : '=';
		$value = $this->escape($value);
		
		if (is_array($value)) {
			$value = "('" . implode("','", $value) . "')";
			
			if ($operator === '=') {
				$operator = 'IN';
			}
		} else {
			$value = "'" . $value . "'";
		}
		
		return "$column $operator $value";
	}
	
	/**
	 * Prepare a WHERE clause using the given filter and comparison operator.
	 * 
	 * Example filter key-values and their SQL equivalents:
	 *     'id'        => 1,       // id = '1'
	 *     'name like' => 'Chris', // name LIKE 'Chris'
	 *     'count >'   => 10,      // count > '10'
	 *     'type in'   => [1, 2],  // type IN (1, 2)
	 *     'type'      => [3, 4]   // type IN (3, 4)
	 * 
	 * Comparison operator between conditions defaults to `'AND'`.
	 * 
	 * @param array  $filter
	 * @param string $comparison   [optional]
	 * @param bool   $excludeWhere [optional]
	 * @return string
	 */
	protected function prepareWhere(array $filter, $comparison = 'AND', $excludeWhere = false) {
		$conditions = array();
		
		foreach ($filter as $column => $value) {
			$conditions[] = $this->prepareFilter($column, $value);
		}
		
		if (!count($conditions)) {
			return null;
		}
		
		$where = $excludeWhere ? '' : 'WHERE ';
		$where .= implode(" $comparison ", $conditions);
		
		return $where;
	}
	
	/**
	 * Prepare an individual order condition.
	 * 
	 * @param string $column
	 * @param string $direction [optional]
	 * @return string
	 */
	protected function prepareOrder($column, $direction = null) {
		$column = $this->escape($column);
		$direction = $direction !== null ? strtoupper($this->escape($direction)) : 'ASC';
		
		return !empty($column) ? "$column $direction" : null;
	}
	
	/**
	 * Prepare an ORDER BY clause using the given order.
	 * 
	 * Example order key-values:
	 *     'column',
	 *     'other_column'   => 'ASC',
	 *     'another_column' => 'DESC
	 * 
	 * Ordered ascending by default.
	 * 
	 * @param array|string $order
	 * @return string
	 */
	protected function prepareOrderBy($order) {
		$conditions = array();
		
		foreach ((array) $order as $key => $value) {
			if (is_numeric($key)) {
				$conditions[] = $this->prepareOrder($value);
			} else {
				$conditions[] = $this->prepareOrder($key, $value);
			}
		}
		
		return count($conditions) ? 'ORDER BY ' . implode(', ', $conditions) : null;
	}
	
	/**
	 * Prepare a LIMIT clause using the given limit and offset.
	 * 
	 * @param int $limit  [optional]
	 * @param int $offset [optional]
	 * @return string
	 */
	protected function prepareLimit($limit = null, $offset = 0) {
		if (!is_numeric($limit)) {
			return null;
		}
		
		$limit = $this->escape($limit);
		$offset = $this->escape($offset);
		
		$query = 'LIMIT ';
		
		if ($offset > 0) {
			$query .= "$offset, ";
		}
		
		return $query . $limit;
	}
	
	/**
	 * Prepare a SELECT statement using the given columns, table and clauses.
	 * 
	 * @param string       $table
	 * @param array|string $columns
	 * @param string       $where [optional]
	 * @param string       $order [optional]
	 * @param string       $limit [optional]
	 */
	protected function prepareSelect($table, $columns, $where = null, $order = null, $limit = null) {
		$table = $this->escape($table);
		
		if (is_array($columns)) {
			$columns = $this->escape($columns);
			$columns = is_array($columns) ? implode(', ', $columns) : $columns;
		} else {
			$columns = $this->escape($columns);
		}
		
		$query = "SELECT $columns FROM $table";
		
		foreach (array($where, $order, $limit) as $clause) {
			if (!empty($clause)) {
				$query .= " $clause";
			}
		}
		
		return $query;
	}
	
	/**
	 * Prepare an INSERT INTO statement using the given table and data.
	 * 
	 * @param string $table
	 * @param array  $data
	 * @return string
	 */
	protected function prepareInsert($table, array $data) {
		$table = $this->escape($table);
		
		$columns = $this->escape(array_keys($data));
		$values  = $this->escape(array_values($data));
		
		$columns = "(" . implode(", ", $columns) . ")";
		$values  = "('" . implode("', '", $values) . "')";
		
		$query = "INSERT INTO $table $columns VALUES $values";
		
		return $query;
	}
	
	/**
	 * Prepare an UPDATE statement with the given table, data and clauses.
	 * 
	 * @param string $table
	 * @param array  $data
	 * @param string $where
	 * @param string $limit
	 * @return string
	 */
	protected function prepareUpdate($table, $data, $where = null, $limit = null) {
		$table = $this->escape($table);
		
		foreach ($data as $key => $value) {
			$key = $this->escape($key);
			$value = $this->escape($value);
			$data[$key] = "$key = '$value'";
		}
		
		$values = implode(', ', $data);
		
		return "UPDATE $table SET $values $where $limit";
	}
	
	/**
	 * Prepare a DELETE statement with the given table and clauses.
	 * 
	 * @param string $table
	 * @param string $where [optional]
	 * @param string $limit [optional]
	 */
	protected function prepareDelete($table, $where = null, $limit = null) {
		$table = $this->escape($table);
		
		if ($table == '*' || !$table || !$where) {
			return null;
		}
		
		return "DELETE FROM $table $where $limit";
	}
}
