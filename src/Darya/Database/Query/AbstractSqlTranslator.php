<?php
namespace Darya\Database\Query;

/**
 * An abstract query translator that prepares SQL common across more than one
 * RDBMS.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class AbstractSqlTranslator {
	
	/**
	 * Escape the given identifier.
	 * 
	 * If the value is an array, it is recursively escaped.
	 * 
	 * If the value is not a string, it is returned unmodified.
	 * 
	 * @param mixed $identifier
	 * @return mixed
	 */
	abstract protected function identifier($identifier);
	
	/**
	 * Prepare the given columns as a string.
	 * 
	 * @param array|string $columns
	 * @return string
	 */
	protected function prepareColumns($columns) {
		if (empty($columns)) {
			return '*';
		}
		
		$columns = $this->identifier($columns);
		
		if (!is_array($columns)) {
			$columns = (array) $columns;
		}
		
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
		
		$column = $this->prepareColumns($column);
		$operator = in_array(strtolower($operator), $this->operators) ? strtoupper($operator) : '=';
		
		$value = $this->escape($value);
		
		if (is_array($value)) {
			$value = "(" . implode(", ", $value) . ")";
			
			if ($operator === '=') {
				$operator = 'IN';
			}
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
			if (strtolower($column) == 'or')  {
				$conditions[] = '(' . $this->prepareWhere($value, 'OR', true) . ')';
			} else {
				$conditions[] = $this->prepareFilter($column, $value);
			}
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
	 * Prepare a SELECT statement using the given columns, table, clauses and
	 * options.
	 * 
	 * @param string       $table
	 * @param array|string $columns
	 * @param string       $where    [optional]
	 * @param string       $order    [optional]
	 * @param string       $limit    [optional]
	 * @param bool         $distinct [optional]
	 * @param bool         $count    [optional]
	 * @return string
	 */
	abstract protected function prepareSelect($table, $columns, $where = null, $order = null, $limit = null, $distinct = false, $count = false);
	
	/**
	 * Prepare an INSERT INTO statement using the given table and data.
	 * 
	 * @param string $table
	 * @param array  $data
	 * @return string
	 */
	protected function prepareInsert($table, array $data) {
		$table = $this->identifier($table);
		
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
	 * @param string $where [optional]
	 * @param string $limit [optional]
	 * @return string
	 */
	abstract protected function prepareUpdate($table, $data, $where = null, $limit = null);
	
	/**
	 * Prepare a DELETE statement with the given table and clauses.
	 * 
	 * @param string $table
	 * @param string $where [optional]
	 * @param string $limit [optional]
	 * @return string
	 */
	abstract protected function prepareDelete($table, $where = null, $limit = null);
	
}