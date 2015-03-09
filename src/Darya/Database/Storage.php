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
	 * @var array
	 */
	protected $operators = array('>=', '<=', '>', '<', '=', '!=', '<>', 'in', 'not in', 'is', 'is not', 'like', 'not like');
	
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
	
	protected function prepareWhere(array $filter, $comparison = 'AND') {
		$conditions = array();
		
		foreach ($filter as $column => $value) {
			$conditions[] = $this->prepareFilter($column, $value);
		}
		
		return count($conditions) ? 'WHERE ' . implode(" $comparison ", $conditions) : null;
	}
	
	protected function prepareOrder($column, $direction = null) {
		$column = $this->connection->escape($column);
		$direction = !is_null($direction) ? $this->connection->escape($direction) : 'ASC';
		
		return !empty($column) ? "$column $direction" : null;
	}
	
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
	
	public function read($table, array $filter = array(), $order = null, $limit = null, $offset = 0) {
		$query = $this->prepareSelect($table, "$table.*", $this->prepareWhere($filter), $this->prepareOrderBy($order), $this->prepareLimit($limit, $offset));
		
		return $this->connection->query($query);
	}
	
	public function count($table, array $filter = array(), $order = null, $limit = null, $offset = 0) {
		$query = $this->prepareSelect($table, '1', $this->prepareWhere($filter), $this->prepareOrderBy($order), $this->prepareLimit($limit, $offset));
		
		return count($this->connection->query($query));
	}
	
	protected function prepareValues(array $values) {
		return array_map(array($this->connection, 'escape'), $values);
	}
	
	protected function prepareInsert($table, array $data) {
		$table = $this->connection->escape($table);
		
		$columns = $this->prepareValues(array_keys($data));
		$values  = $this->prepareValues(array_values($data));
		
		$columns = "(" . implode(", ", $columns) . ")";
		$values  = "('" . implode("', '", $values) . "')";
		
		$query = "INSERT INTO $table $columns VALUES $values";
		
		return $query;
	}
	
	public function create($table, $data) {
		return $this->connection->query($this->prepareInsert($table, $data));
	}
	
	protected function prepareUpdate($table, $data, $where = null, $limit = null) {
		$table = $this->connection->escape($table);
		
		foreach ($data as $key => $value) {
			$data[$key] = "$key = '$value'";
		}
		
		$values = implode(', ', $data);
		
		return "UPDATE $table SET $values $where $limit";
	}
	
	public function update($table, $data, array $filter = array(), $limit = null) {
		$where = $this->prepareWhere($filter);
		$limit = $this->prepareLimit($limit);
		
		if (!$where) {
			return null;
		}
		
		$query = $this->prepareUpdate($table, $data, $where, $limit);
		
		return $this->connection->query($query);
	}
	
	public function delete($table, array $filter = array(), $limit = null) {
		$table = $this->connection->escape($table);
		$where = $this->prepareWhere($filter);
		
		if ($table == '*' || !$table || !$where) {
			return null;
		}
		
		$limit = $this->prepareLimit($limit);
		
		$query = "DELETE FROM $table $where $limit";
		
		return $this->connection->query($query);
	}
	
}
