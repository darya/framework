<?php
namespace Darya\Database\Query;

use Exception;
use Darya\Database;
use Darya\Database\Query\Translator;
use Darya\Storage;

/**
 * An abstract query translator that prepares SQL common across more than one
 * RDBMS.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class AbstractSqlTranslator implements Translator {
	
	/**
	 * Filter comparison operators.
	 * 
	 * @var array
	 */
	protected $operators = array('>=', '<=', '>', '<', '=', '!=', '<>', 'in', 'not in', 'is', 'is not', 'like', 'not like');
	
	/**
	 * Placeholder for values in prepared queries.
	 * 
	 * @var string
	 */
	protected $placeholder = '?';
	
	/**
	 * Concatenates the given set of strings that aren't empty.
	 * 
	 * Runs implode() after filtering out empty elements.
	 * 
	 * Delimiter defaults to a single whitespace character.
	 * 
	 * @param array  $strings
	 * @param string $delimiter [optional]
	 * @return string
	 */
	protected static function concatenate($strings, $delimiter = ' ') {
		$strings = array_filter($strings, function($value) {
			return !empty($value);
		});
		
		return implode($delimiter, $strings);
	}
	
	/**
	 * Determine whether the given limit and offset will make a difference to
	 * a statement.
	 * 
	 * Simply determines whether both are non-zero integers.
	 * 
	 * @param int $limit
	 * @param int $offset
	 * @return bool
	 */
	protected static function limitIsUseful($limit, $offset) {
		return (int) $limit !== 0 || (int) $offset !== 0;
	}
	
	/**
	 * Translate the given storage query into an SQL query.
	 * 
	 * @param Storage\Query $storageQuery
	 * @return Database\Query
	 * @throws Exception
	 */
	public function translate(Storage\Query $storageQuery) {
		$type = $storageQuery->type;
		
		$method = 'translate' . ucfirst($type);
		
		if (!method_exists($this, $method)) {
			throw new Exception("Could not translate query of unknown type '$type'");
		}
		
		$query = call_user_func_array(array($this, $method), array($storageQuery));
		
		return $query;
	}
	
	/**
	 * Translate a query that creates a record.
	 * 
	 * @param Storage\Query $storageQuery
	 * @return Database\Query
	 */
	protected function translateCreate(Storage\Query $storageQuery) {
		return new Database\Query(
			$this->prepareInsert($storageQuery->resource, $storageQuery->data),
			$this->parameters($storageQuery)
		);
	}
	
	/**
	 * Translate a query that reads records.
	 * 
	 * @param Storage\Query $storageQuery
	 * @return Database\Query
	 */
	protected function translateRead(Storage\Query $storageQuery) {
		return new Database\Query(
			$this->prepareSelect($storageQuery->resource,
				$this->prepareColumns($storageQuery->fields),
				$this->prepareJoins($storageQuery->joins),
				$this->prepareWhere($storageQuery->filter),
				$this->prepareOrderBy($storageQuery->order),
				$this->prepareLimit($storageQuery->limit, $storageQuery->offset),
				$storageQuery->distinct
			),
			$this->parameters($storageQuery)
		);
	}
	
	/**
	 * Translate a query that updates records.
	 * 
	 * @param Storage\Query $storageQuery
	 * @return Database\Query
	 */
	protected function translateUpdate(Storage\Query $storageQuery) {
		return new Database\Query(
			$this->prepareUpdate($storageQuery->resource, $storageQuery->data,
				$this->prepareWhere($storageQuery->filter),
				$this->prepareLimit($storageQuery->limit, $storageQuery->offset)
			),
			$this->parameters($storageQuery)
		);
	}
	
	/**
	 * Translate a query that deletes records.
	 * 
	 * @param Storage\Query $storageQuery
	 * @return Database\Query
	 */
	protected function translateDelete(Storage\Query $storageQuery) {
		return new Database\Query(
			$this->prepareDelete($storageQuery->resource,
				$this->prepareWhere($storageQuery->filter),
				$this->prepareLimit($storageQuery->limit, $storageQuery->offset)
			),
			$this->parameters($storageQuery)
		);
	}
	
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
	 * Prepare the given value for a prepared query.
	 * 
	 * If the value is a storage query, it is translated.
	 * 
	 * If the value is an array, it is recursively prepared.
	 * 
	 * @param array|string $value
	 * @return array|string
	 */
	protected function value($value) {
		if ($value instanceof Storage\Query) {
			$query = $this->translate($value);
			
			return "($query)";
		}
		
		if (is_array($value)) {
			return array_map(array($this, 'value'), $value);
		}
		
		return $this->resolve($value);
	}
	
	/**
	 * Resolve a placeholder or constant for the given parameter value.
	 * 
	 * @param mixed $value
	 * @return string
	 */
	protected function resolve($value) {
		if ($value === null) {
			return 'NULL';
		}
		
		if (is_bool($value)) {
			return $value ? 'TRUE' : 'FALSE';
		}
		
		return $this->placeholder;
	}
	
	/**
	 * Determine whether the given value resolves a placeholder.
	 * 
	 * @param mixed $value
	 * @return bool
	 */
	protected function resolvesPlaceholder($value) {
		return $this->resolve($value) === $this->placeholder;
	}
	
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
		
		$columns = (array) $this->identifier($columns);
		
		return implode(', ', $columns);
	}
	
	/**
	 * Prepare the given conditional operator.
	 * 
	 * Returns the equals operator if given value is not in the set of valid
	 * operators.
	 * 
	 * @param string $operator
	 * @return string
	 */
	protected function prepareRawOperator($operator) {
		$operator = trim($operator);
		
		return in_array(strtolower($operator), $this->operators) ? strtoupper($operator) : '=';
	}
	
	/**
	 * Prepare an appropriate conditional operator for the given value.
	 * 
	 * @param string $operator
	 * @param mixed  $value    [optional]
	 * @return string
	 */
	protected function prepareOperator($operator, $value = null) {
		$operator = $this->prepareRawOperator($operator);
		
		if (!$this->resolvesPlaceholder($value)) {
			if ($operator === '=') {
				$operator = 'IS';
			}
			
			if ($operator === '!=') {
				$operator = 'IS NOT';
			}
		}
		
		if (is_array($value)) {
			if ($operator === '=') {
				$operator = 'IN';
			}
		
			if ($operator === '!=') {
				$operator = 'NOT IN';
			}
		}
		
		return $operator;
	}
	
	/**
	 * Prepare a join type.
	 * 
	 * @param string $type
	 * @return string
	 */
	protected function prepareJoinType($type) {
		if (in_array($type, array('left', 'right'))) {
			return strtoupper($type) . ' JOIN';
		}
		
		return 'JOIN';
	}
	
	/**
	 * Prepare a join table.
	 * 
	 * @param Storage\Query\Join $join
	 * @return string
	 */
	protected function prepareJoinTable(Storage\Query\Join $join) {
		$table = $this->identifier($join->resource);
		$alias = $this->identifier($join->alias);
		
		return $alias ? "$table $alias" : $table;
	}
	
	/**
	 * Prepare a single join condition.
	 * 
	 * TODO: Make this generic for WHERE or JOIN clauses
	 * 
	 * @param string $condition
	 * @return string
	 */
	protected function prepareJoinCondition($condition) {
		$parts = preg_split('/\s+/', $condition, 3);
		
		if (count($parts) < 3) {
			return null;
		}
		
		list($first, $operator, $second) = $parts;
		
		return static::concatenate(array(
			$this->identifier($first),
			$this->prepareRawOperator($operator),
			$this->identifier($second)
		));
	}
	
	/**
	 * Prepare a join's conditions.
	 * 
	 * @param Storage\Query\Join $join
	 * @return string
	 */
	protected function prepareJoinConditions(Storage\Query\Join $join) {
		$result = null;
		
		$conditions = array();
		
		foreach ($join->conditions as $condition) {
			$conditions[] = $this->prepareJoinCondition($condition);
		}
		
		$conditions = array_merge($conditions, $this->prepareFilter($join->filter));
		
		return static::concatenate($conditions, ' AND ');
	}
	
	/**
	 * Prepare an individual table join.
	 * 
	 * @param Storage\Query\Join $join
	 * @return string
	 */
	protected function prepareJoin(Storage\Query\Join $join) {
		$table = $this->prepareJoinTable($join);
		$conditions = $this->prepareJoinConditions($join);
		
		$clause = $table && $conditions ? "$table ON $conditions" : $table;
		
		if (empty($clause)) {
			return null;
		}
		
		$type = $this->prepareJoinType($join->type);
		
		return "$type $clause";
	}
	
	/**
	 * Prepare table joins.
	 * 
	 * @param array $joins
	 * @return string
	 */
	protected function prepareJoins(array $joins) {
		$clauses = array();
		
		foreach ($joins as $join) {
			$clauses[] = $this->prepareJoin($join);
		}
		
		return static::concatenate($clauses);
	}
	
	/**
	 * Prepare an individual filter condition.
	 * 
	 * @param string $column
	 * @param mixed  $value
	 * @return string
	 */
	protected function prepareFilterCondition($column, $value) {
		list($column, $operator) = array_pad(preg_split('/\s+/', $column, 2), 2, null);
		
		$column = $this->prepareColumns($column);
		$operator = $this->prepareOperator($operator, $value);
		$value = $this->value($value);
		
		if (is_array($value)) {
			$value = "(" . implode(", ", $value) . ")";
		}
		
		return "$column $operator $value";
	}
	
	/**
	 * Prepare a filter as a set of query conditions.
	 * 
	 * TODO: Could numeric keys be dealt with by prepareJoinCondition()?
	 * 
	 * @param array $filter
	 * @return array
	 */
	protected function prepareFilter(array $filter) {
		$conditions = array();
		
		foreach ($filter as $column => $value) {
			if (strtolower($column) == 'or') {
				$conditions[] = '(' . $this->prepareWhere($value, 'OR', true) . ')';
			} else {
				$conditions[] = $this->prepareFilterCondition($column, $value);
			}
		}
		
		return $conditions;
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
	 * Comparison operator between conditions defaults to 'AND'.
	 * 
	 * @param array  $filter
	 * @param string $comparison   [optional]
	 * @param bool   $excludeWhere [optional]
	 * @return string
	 */
	protected function prepareWhere(array $filter, $comparison = 'AND', $excludeWhere = false) {
		$conditions = $this->prepareFilter($filter);
		
		if (empty($conditions)) {
			return null;
		}
		
		$clause = implode(" $comparison ", $conditions);
		
		return !$excludeWhere ? "WHERE $clause" : $clause;
	}
	
	/**
	 * Prepare an individual order condition.
	 * 
	 * @param string $column
	 * @param string $direction [optional]
	 * @return string
	 */
	protected function prepareOrder($column, $direction = null) {
		$column = $this->identifier($column);
		$direction = $direction !== null ? strtoupper($direction) : 'ASC';
		
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
	 * TODO: Simplify this so that prepareSelect only actually prepares the
	 *       SELECT and FROM clauses. The rest could be concatenated by
	 *       translateRead().
	 * 
	 * @param string       $table
	 * @param array|string $columns
	 * @param string       $joins    [optional]
	 * @param string       $where    [optional]
	 * @param string       $order    [optional]
	 * @param string       $limit    [optional]
	 * @param bool         $distinct [optional]
	 * @return string
	 */
	abstract protected function prepareSelect($table, $columns, $joins = null, $where = null, $order = null, $limit = null, $distinct = false);
	
	/**
	 * Prepare an INSERT INTO statement using the given table and data.
	 * 
	 * @param string $table
	 * @param array  $data
	 * @return string
	 */
	protected function prepareInsert($table, array $data) {
		$table = $this->identifier($table);
		
		$columns = $this->identifier(array_keys($data));
		$values  = $this->value(array_values($data));
		
		$columns = "(" . implode(", ", $columns) . ")";
		$values  = "(" . implode(", ", $values) . ")";
		
		return static::concatenate(array('INSERT INTO', $table, $columns, 'VALUES', $values));
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
	
	/**
	 * Prepare the given data as an array of prepared query parameters.
	 * 
	 * @param array $data
	 * @return array
	 */
	protected function dataParameters($data) {
		$parameters = array();
		
		foreach ($data as $value) {
			if ($this->resolvesPlaceholder($value)) {
				$parameters[] = $value;
			}
		}
		
		return $parameters;
	}
	
	/**
	 * Prepare a set of query parameters from the given set of joins.
	 * 
	 * @param Storage\Query\Join[] $joins
	 * @return array
	 */
	protected function joinsParameters($joins) {
		$parameters = array();
		
		foreach ($joins as $join) {
			$parameters = array_merge($parameters, $this->filterParameters($join->filter));
		}
		
		return $parameters;
	}
	
	/**
	 * Prepare the given filter as an array of prepared query parameters.
	 * 
	 * @param array $filter
	 * @return array
	 */
	protected function filterParameters($filter) {
		$parameters = array();
		
		foreach ($filter as $index => $value) {
			if (is_array($value)) {
				if (strtolower($index) === 'or') {
					$parameters = array_merge($parameters, $this->filterParameters($value));
				} else {
					foreach ($value as $in) {
						if ($this->resolvesPlaceholder($value)) {
							$parameters[] = $in;
						}
					}
				}
				
				continue;
			}
			
			if ($value instanceof Storage\Query) {
				$parameters = array_merge($parameters, $this->parameters($value));
				
				continue;
			}
			
			if ($this->resolvesPlaceholder($value)) {
				$parameters[] = $value;
			}
		}
		
		return $parameters;
	}
	
	/**
	 * Retrieve an array of parameters from the given query for executing a
	 * prepared query.
	 * 
	 * @param Storage\Query $storageQuery
	 * @return array
	 */
	public function parameters(Storage\Query $storageQuery) {
		$parameters = array();
		
		if (in_array($storageQuery->type, array(Storage\Query::CREATE, Storage\Query::UPDATE))) {
			$parameters = $this->dataParameters($storageQuery->data);
		}
		
		$parameters = array_merge(
			$parameters,
			$this->joinsParameters($storageQuery->joins),
			$this->filterParameters($storageQuery->filter)
		);
		
		return $parameters;
	}
	
}
