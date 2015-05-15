<?php
namespace Darya\Database;

use Darya\Database\Connection;
use Darya\Storage\Aggregational;
use Darya\Storage\Readable;
use Darya\Storage\Modifiable;
use Darya\Storage\Searchable;

/**
 * Darya's database storage implementation.
 * 
 * TODO: Extract preparation methods as a query builder or fluent query class.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Storage implements Aggregational, Readable, Modifiable, Searchable {
	
	/**
	 * @var \Darya\Database\Connection
	 */
	protected $connection;
	
	/**
	 * @var array Filter comparison operators
	 */
	protected $operators = array('>=', '<=', '>', '<', '=', '!=', '<>', 'in', 'not in', 'is', 'is not', 'like', 'not like');
	
	/**
	 * Instantiate a database-driven data store.
	 * 
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
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
	 * Retrieve the distinct values of the given resource field.
	 * 
	 * @param string $table
	 * @param string $field
	 * @param array  $filter   [optional]
	 * @param array  $order    [optional]
	 * @param int    $limit    [optional]
	 * @param int    $offset   [optional]
	 */
	public function distinct($table, $field, array $filter = array(), $order = array(), $limit = null, $offset = 0) {
		$query = $this->prepareSelect($table, "DISTINCT $field", $this->prepareWhere($filter), $this->prepareOrderBy($order), $this->prepareLimit($limit, $offset));
		
		return $this->connection->query($query)->data;
	}
	
	/**
	 * Retrieve database rows that match the given criteria.
	 * 
	 * @param string       $table
	 * @param array        $filter [optional]
	 * @param array|string $order  [optional]
	 * @param int          $limit  [optional]
	 * @param int          $offset [optional]
	 * @return array
	 */
	public function read($table, array $filter = array(), $order = null, $limit = null, $offset = 0) {
		$query = $this->prepareSelect($table, "$table.*", $this->prepareWhere($filter), $this->prepareOrderBy($order), $this->prepareLimit($limit, $offset));
		
		return $this->connection->query($query)->data;
	}
	
	/**
	 * Count the rows that match the given filter.
	 * 
	 * @param string $table
	 * @param array  $filter [optional]
	 * @param array  $order  [optional]
	 * @param int    $limit  [optional]
	 * @param int    $offset [optional]
	 * @return int
	 */
	public function count($table, array $filter = array(), $order = null, $limit = null, $offset = 0) {
		$query = $this->prepareSelect($table, '1', $this->prepareWhere($filter), $this->prepareOrderBy($order), $this->prepareLimit($limit, $offset));
		
		return $this->connection->query($query)->count;
	}
	
	/**
	 * Insert a record with the given data into the given table.
	 * 
	 * Returns the ID of the new row or false if an error occurred.
	 * 
	 * @param string $table
	 * @param array  $data
	 * @return int|bool
	 */
	public function create($table, $data) {
		$query = $this->prepareInsert($table, $data);
		
		return $this->connection->query($query)->insertId;
	}
	
	/**
	 * Update rows that match the given criteria using the given data.
	 * 
	 * Returns the number of rows affected by the update operation. If no rows
	 * are affected, returns true if there were no errors, false otherwise.
	 * 
	 * Refuses to perform the query if the given filter evaluates to an
	 * empty where clause.
	 * 
	 * @param string $table
	 * @param array  $data
	 * @param array  $filter [optional]
	 * @param int    $limit  [optional]
	 * @return int|bool
	 */
	public function update($table, $data, array $filter = array(), $limit = null) {
		if (!$data) {
			return true;
		}
			
		$where = $this->prepareWhere($filter);
		$limit = $this->prepareLimit($limit);
		
		if (!$where) {
			return null;
		}
		
		$query = $this->prepareUpdate($table, $data, $where, $limit);
		$result = $this->connection->query($query);
		
		return $result->affected ?: !$this->errors();
	}
	
	/**
	 * Delete rows from the given table using the given filter and limit.
	 * 
	 * Returns the number of rows affected by the delete operation.
	 * 
	 * Refuses to perform the operation if the table is a wildcard ('*'), empty,
	 * or if the given filter evaluates to an empty where clause.
	 * 
	 * @param string $table
	 * @param array  $filter [optional]
	 * @param int    $limit  [optional]
	 * @return int
	 */
	public function delete($table, array $filter = array(), $limit = null) {
		$table = $this->escape($table);
		$where = $this->prepareWhere($filter);
		
		if ($table == '*' || !$table || !$where) {
			return null;
		}
		
		$limit = $this->prepareLimit($limit);
		$query = "DELETE FROM $table $where $limit";
		
		$result = $this->connection->query($query);
		
		return $result->affected;
	}
	
	/**
	 * Retrieve any errors that occured with the last operation.
	 * 
	 * @return array
	 */
	public function errors() {
		$error = $this->connection->error();
		
		return $error['msg'];
	}
	
	/**
	 * Search for rows in the given table with fields that match the given query
	 * and criteria.
	 * 
	 * @param string       $table
	 * @param string       $query
	 * @param array|string $columns
	 * @param array        $filter [optional]
	 * @param array|string $order  [optional]
	 * @param int          $limit  [optional]
	 * @param int          $offset [optional]
	 */
	public function search($table, $query, $columns = array(), array $filter = array(), $order = array(), $limit = null, $offset = 0) {
		if (empty($query) || empty($columns)) {
			return $this->read($table, $filter, $order, $limit, $offset);
		}
		
		list($table, $query) = $this->escape(array($table, $query));
		
		$search = array();
		
		foreach ((array) $columns as $column) {
			$search[$this->escape($column) . ' like'] = '%' . $this->escape($query) . '%';
		}
		
		$where = 'WHERE (' . $this->prepareWhere($search, 'OR', true) . ')';
		$conditions = $this->prepareWhere($filter, 'AND', true);
		
		if (!empty($conditions)) {
			$where .= ' AND ' . $conditions;
		}
		
		$orderby = $this->prepareOrderBy($order);
		$limit = $this->prepareLimit($limit, $offset);
		$query = $this->prepareSelect($table, "$table.*", $where, $orderby, $limit);
		
		return $this->connection->query($query)->data;
	}
	
}
