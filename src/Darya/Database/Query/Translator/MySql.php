<?php
namespace Darya\Database\Query\Translator;

use Darya\Database\Connection\MySql as MySqlConnection;
use Darya\Database\Query\AbstractSqlTranslator;

/**
 * Darya's MySQL query translator.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class MySql extends AbstractSqlTranslator {
	
	/**
	 * @var Database\Connection
	 */
	protected $connection;
	
	/**
	 * Instantiate a new MySQL query translator.
	 * 
	 * @param MySqlConnection $connection
	 */
	public function __construct(MySqlConnection $connection) {
		$this->connection = $connection;
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
			return array_map(array($this, 'identifier'), $identifier);
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
		if (!is_numeric($limit) || !is_numeric($offset) || empty($limit)) {
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
	 * @return string
	 */
	protected function prepareSelect($table, $columns, $where = null, $order = null, $limit = null, $distinct = false) {
		$table = $this->identifier($table);
		
		$distinct = $distinct ? 'DISTINCT' : '';
		
		return static::concatenate(array('SELECT', $distinct, $columns, 'FROM', $table, $where, $order, $limit));
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
			$value = $this->value($value);
			$data[$key] = "$column = $value";
		}
		
		$values = implode(', ', $data);
		
		return static::concatenate(array('UPDATE', $table, 'SET', $values, $where, $limit));
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
		
		return static::concatenate(array('DELETE FROM', $table, $where, $limit));
	}
	
}
