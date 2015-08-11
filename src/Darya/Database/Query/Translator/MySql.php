<?php
namespace Darya\Database\Query\Translator;

use Darya\Database;
use Darya\Database\Query\AbstractSqlTranslator;
use Darya\Database\Query\Translator;
use Darya\Storage;

/**
 * Darya's MySQL query translator.
 * 
 * TODO: Separate the switch statement bodies out into their own methods.
 * TODO: Parameterised queries.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class MySql extends AbstractSqlTranslator implements Translator {
	
	/**
	 * @var Database\Connection
	 */
	protected $connection;
	
	/**
	 * @var array Filter comparison operators
	 */
	protected $operators = array('>=', '<=', '>', '<', '=', '!=', '<>', 'in', 'not in', 'is', 'is not', 'like', 'not like');
	
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
					$this->prepareSelect($storageQuery->resource,
						$this->prepareColumns($storageQuery->fields),
						$storageQuery->distinct,
						$this->prepareWhere($storageQuery->filter),
						$this->prepareOrderBy($storageQuery->order),
						$this->prepareLimit($storageQuery->limit, $storageQuery->offset)
					)
				);
				
				break;
			case Storage\Query::UPDATE:
				$query = new Database\Query(
					$this->prepareUpdate($storageQuery->resource, $storageQuery->data,
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
	 * @return array|string
	 */
	protected function escape($value) {
		if (is_array($value)) {
			return array_map(array($this, 'escape'), $value);
		}
		
		if (is_string($value)) {
			return "'" . $this->connection->escape($value) . "'";
		}
		
		return $value;
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
	protected function identifier($identifier) {
		if (is_array($identifier)) {
			return array_map(array($this, 'identifier', $identifier));
		}
		
		if (!is_string($identifier)) {
			return $identifier;
		}
		
		$split = explode('.', $identifier, 2);
		
		foreach ($split as $index => $value) {
			$split[$index] = '`' . $value . '`';
		}
		
		return implode('.', $split);
	}
	
	/**
	 * Prepare a LIMIT clause using the given limit and offset.
	 * 
	 * @param int $limit  [optional]
	 * @param int $offset [optional]
	 * @return string
	 */
	protected function prepareLimit($limit = null, $offset = 0) {
		if (!is_numeric($limit) || !is_numeric($offset)) {
			return null;
		}
		
		$limit = (int) $limit;
		$offset = (int) $offset;
		
		$query = 'LIMIT ';
		
		if ($offset > 0) {
			$query .= "$offset, ";
		}
		
		return $query . $limit;
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
	protected function prepareSelect($table, $columns, $where = null, $order = null, $limit = null, $distinct = false, $count = false) {
		$table = $this->identifier($table);
		
		$distinct = $distinct ? 'DISTINCT' : '';
		
		$query = "SELECT $distinct $columns FROM $table";
		
		foreach (array($where, $order, $limit) as $clause) {
			if (!empty($clause)) {
				$query .= " $clause";
			}
		}
		
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
	protected function prepareUpdate($table, $data, $where = null, $limit = null) {
		$table = $this->identifier($table);
		
		foreach ($data as $key => $value) {
			$column = $this->identifier($key);
			$value = $this->escape($value);
			$data[$key] = "$column = $value";
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
	 * @return string
	 */
	protected function prepareDelete($table, $where = null, $limit = null) {
		$table = $this->identifier($table);
		
		if ($table == '*' || !$table || !$where) {
			return null;
		}
		
		return "DELETE FROM $table $where $limit";
	}
	
}