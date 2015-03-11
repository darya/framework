<?php
namespace Darya\Database;

use Darya\Database\DatabaseInterface;
use Darya\Storage\Readable;
use Darya\Storage\Modifiable;

class Storage implements Readable, Modifiable {
	
	/**
	 * @var \Darya\Database\DatabaseInterface
	 */
	protected $connection;
	
	/**
	 * @var array Filter comparison operators
	 */
	protected $operators = array('>=', '<=', '>', '<', '=', '!=', '<>', 'in', 'not in', 'is', 'is not', 'like', 'not like');
	
	/**
	 * Instantiate a database-driven data store.
	 * 
	 * @param DatabaseInterface $connection
	 * @param string            $prefix
	 */
	public function __construct(DatabaseInterface $connection) {
		$this->connection = $connection;
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
		$column = $this->connection->escape($column);
		$operator = in_array(strtolower($operator), $this->operators) ? $operator : '=';
		
		if (is_array($value)) {
			$value = array_map(array($this->connection, 'escape'), $value);
			$value = "('" . implode("','", $value) . "')";
			
			if ($operator === '=') {
				$operator = 'IN';
			}
		} else {
			$value = "'" . $this->connection->escape($value) . "'";
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
	 *     'type in'   => [1, 2]   // type IN (1, 2)
	 *     'type'      => [3, 4]   // type IN (3, 4)
	 * 
	 * Comparison operator between conditions defaults to `'AND'`.
	 * 
	 * @param array  $filter
	 * @param string $comparison [optional]
	 * @return string
	 */
	protected function prepareWhere(array $filter, $comparison = 'AND') {
		$conditions = array();
		
		foreach ($filter as $column => $value) {
			$conditions[] = $this->prepareFilter($column, $value);
		}
		
		return count($conditions) ? 'WHERE ' . implode(" $comparison ", $conditions) : null;
	}
	
	/**
	 * Prepare an individual order condition.
	 * 
	 * @param string $column
	 * @param string $direction [optional]
	 * @return string
	 */
	protected function prepareOrder($column, $direction = null) {
		$column = $this->connection->escape($column);
		$direction = !is_null($direction) ? $this->connection->escape($direction) : 'ASC';
		
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
		
		$query = 'LIMIT ';
		
		if ($offset > 0) {
			$query .= "$offset, ";
		}
		
		return $query . $limit;
	}
	
	/**
	 * Prepare a SELECT statement using the given columns, table and clauses.
	 * 
	 * @param array|string $columns
	 * @param string       $table
	 * @param string       $where
	 * @param string       $order
	 * @param string       $limit
	 */
	protected function prepareSelect($table, $columns, $where = null, $order = null, $limit = null) {
		$table = $this->connection->escape($table);
		$columns = is_array($columns) ? implode(', ', $columns) : $columns;
		$query = "SELECT $columns FROM $table";
		
		foreach (array($where, $order, $limit) as $clause) {
			if (!empty($clause)) {
				$query .= " $clause";
			}
		}
		
		return $query;
	}
	
	/**
	 * Retrieve database rows that match the given criteria.
	 * 
	 * @param string       $table
	 * @param array        $filter
	 * @param array|string $order
	 * @param int          $limit
	 * @param int          $offset
	 * @return array
	 */
	public function read($table, array $filter = array(), $order = null, $limit = null, $offset = 0) {
		$query = $this->prepareSelect($table, "$table.*", $this->prepareWhere($filter), $this->prepareOrderBy($order), $this->prepareLimit($limit, $offset));
		
		return $this->connection->query($query);
	}
	
	/**
	 * Count the rows that match the given filter.
	 * 
	 * @param string $table
	 * @param array  $filter
	 * @return int
	 */
	public function count($table, array $filter = array(), $order = null, $limit = null, $offset = 0) {
		$query = $this->prepareSelect($table, '1', $this->prepareWhere($filter), $this->prepareOrderBy($order), $this->prepareLimit($limit, $offset));
		
		return count($this->connection->query($query));
	}
	
	/**
	 * Escape the the values of the given array.
	 * 
	 * @param array $values
	 * @return array
	 */
	protected function prepareValues(array $values) {
		return array_map(array($this->connection, 'escape'), $values);
	}
	
	/**
	 * Prepare an INSERT INTO statement using the given table and data.
	 * 
	 * @param string $table
	 * @param array  $data
	 * @return string
	 */
	protected function prepareInsert($table, array $data) {
		$table = $this->connection->escape($table);
		
		$columns = $this->prepareValues(array_keys($data));
		$values  = $this->prepareValues(array_values($data));
		
		$columns = "(" . implode(", ", $columns) . ")";
		$values  = "('" . implode("', '", $values) . "')";
		
		$query = "INSERT INTO $table $columns VALUES $values";
		
		return $query;
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
		$result = $this->connection->query($query, true);
		
		return isset($result['insert_id']) ? $result['insert_id'] : false;
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
		$table = $this->connection->escape($table);
		
		foreach ($data as $key => $value) {
			$data[$key] = "$key = '$value'";
		}
		
		$values = implode(', ', $data);
		
		return "UPDATE $table SET $values $where $limit";
	}
	
	/**
	 * Update rows that match the given criteria using the given data.
	 * 
	 * Returns the number of rows affected by the update operation.
	 * 
	 * Refuses to perform the operation if the given filter evaluates to an
	 * empty where clause.
	 * 
	 * @param string $table
	 * @param array  $data
	 * @param array  $filter
	 * @param int    $limit
	 * @return int
	 */
	public function update($table, $data, array $filter = array(), $limit = null) {
		$where = $this->prepareWhere($filter);
		$limit = $this->prepareLimit($limit);
		
		if (!$where) {
			return null;
		}
		
		$query = $this->prepareUpdate($table, $data, $where, $limit);
		
		$result = $this->connection->query($query, true);
		
		return isset($result['affected']) ? $result['affected'] : 0;
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
	 * @param array  $filter
	 * @param int    $limit
	 * @return int
	 */
	public function delete($table, array $filter = array(), $limit = null) {
		$table = $this->connection->escape($table);
		$where = $this->prepareWhere($filter);
		
		if ($table == '*' || !$table || !$where) {
			return null;
		}
		
		$limit = $this->prepareLimit($limit);
		$query = "DELETE FROM $table $where $limit";
		
		$result = $this->connection->query($query, true);
		
		return isset($result['affected']) ? $result['affected'] : 0;
	}
	
}