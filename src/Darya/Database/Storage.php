<?php
namespace Darya\Database;

use Darya\Database\DatabaseInterface;
use Darya\Storage\Readable;
use Darya\Storage\Modifiable;

class Storage implements Readable {
	
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
	
	protected function prepareSelect($columns, $table, $where = null, $order = null, $limit = null) {
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
		$query = $this->prepareSelect('*', $table, $this->prepareWhere($filter), $this->prepareOrderBy($order));
		
		return $this->connection->query($query);
	}
	
	public function count($table, array $filter = array(), $order = null, $limit = null, $offset = 0) {
		$query = $this->prepareSelect('1', $table, $this->prepareWhere($filter), $this->prepareOrderBy($order));
		
		return count($this->connection->query($query));
	}
	
}
