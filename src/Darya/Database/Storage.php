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
		list($column, $operator) = explode(' ', $column, 2);
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
	
	protected function prepareWhere($filter, $comparison = 'AND') {
		$conditions = array();
		
		foreach ($filter as $column => $value) {
			$conditions[] = $this->prepareFilter($column, $value);
		}
		
		return implode(" $comparison ", $conditions);
	}
	
	public function read($table, $filter = array(), $order = null, $limit = null, $offset = 0) {
		$where = $this->prepareWhere($filter);
		
		$query = "SELECT * FROM $table";
		
		if ($where) {
			$query .= "WHERE $where";
		}
		
		return $this->connection->query($query);
	}
	
}
