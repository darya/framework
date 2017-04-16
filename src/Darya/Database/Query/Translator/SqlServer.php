<?php
namespace Darya\Database\Query\Translator;

use Darya\Database;
use Darya\Database\Query\AbstractSqlTranslator;
use Darya\Storage;

/**
 * Darya's SQL Server query translator.
 *
 * TODO: Bracketed identifiers; [identifier].[identifier]
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class SqlServer extends AbstractSqlTranslator
{
	/**
	 * Translate a query that reads records.
	 *
	 * @param Storage\Query $query
	 * @return Database\Query
	 */
	protected function translateRead(Storage\Query $query)
	{
		// Unfortunately, we have to deal with SQL Server's awkwardness if an
		// offset is given
		if ($query->offset > 0) {
			return new Database\Query(
				$this->prepareAnsiOffsetSelect($query),
				$this->parameters($query)
			);
		}
		
		return parent::translateRead($query);
	}
	
	/**
	 * Resolve the given value as an identifier.
	 * 
	 * @param mixed $identifier
	 * @return mixed
	 */
	protected function resolveIdentifier($identifier)
	{
		return $identifier;
	}
	
	/**
	 * Prepare a LIMIT clause using the given limit and offset.
	 * 
	 * @param int $limit  [optional]
	 * @param int $offset [optional]
	 * @return string
	 */
	protected function prepareLimit($limit = 0, $offset = 0)
	{
		if (!static::limitIsUseful($limit, $offset)) {
			return null;
		}
		
		$limit = (int) $limit;
		
		return "TOP $limit";
	}
	
	/**
	 * Prepare a SELECT statement using the given columns, table, clauses and
	 * options.
	 * 
	 * @param string       $table
	 * @param array|string $columns
	 * @param string       $joins     [optional]
	 * @param string       $where     [optional]
	 * @param string       $order     [optional]
	 * @param string       $limit     [optional]
	 * @param string       $groupings [optional]
	 * @param string       $having    [optional]
	 * @param bool         $distinct  [optional]
	 * @return string
	 */
	protected function prepareSelect(
		$table,
		$columns,
		$joins = null,
		$where = null,
		$order = null,
		$limit = null,
		$groupings = null,
		$having = null,
		$distinct = false
	) {
		$table = $this->identifier($table);
		
		$distinct = $distinct ? 'DISTINCT' : '';
		
		return static::concatenate(array('SELECT', $distinct, $limit, $columns, 'FROM', $table, $joins, $where, $groupings, $having, $order));
	}
	
	/**
	 * Prepare the column selection for an ANSI offset select statement.
	 *
	 * Cheers Microsoft.
	 *
	 * @param string $columns
	 * @param array|string $order
	 * @return string
	 */
	protected function prepareAnsiOffsetSelectColumns($columns, $order)
	{
		// An order by clause is required by ANSI offset select statements; we
		// can trick SQL Server into behaving by selecting 0 if none is given
		$orderBy = empty($order) ? 'ORDER BY (SELECT 0)' : $this->prepareOrderBy($order);
		
		return implode(', ', array(
			$columns,
			"ROW_NUMBER() OVER ({$orderBy}) row_number"
		));
	}
	
	/**
	 * Prepare an ANSI offset select statement.
	 *
	 * Cheers Microsoft.
	 *
	 * @param Storage\Query $query
	 * @return string
	 */
	protected function prepareAnsiOffsetSelect(Storage\Query $query)
	{
		// Prepare RDBMS specific clauses if we need to
		$joins = null;
		$groupBy = null;
		$having = null;
		
		if ($query instanceof Database\Storage\Query) {
			$joins = $this->prepareJoins($query->joins);
			$groupBy = $this->prepareGroupBy($query->groupings);
			$having = $this->prepareHaving($query->having);
		}
		
		// Build the inner query without its limit, but with the row number and
		// order by clause included with the columns
		$columns = $this->prepareColumns($query->fields);
		
		$innerSelect = $this->prepareSelect(
			$query->resource,
			$this->prepareAnsiOffsetSelectColumns($columns, $query->order),
			$joins,
			$this->prepareWhere($query->filter),
			null,
			null,
			$groupBy,
			$having,
			$query->distinct
		);
		
		// Construct the outer query that uses the row_number from the inner
		// query to achieve the desired offset
		return static::concatenate(array(
			'SELECT',
			$this->prepareLimit($query->limit),
			$columns,
			'FROM',
			"($innerSelect)",
			'query_results',
			"WHERE row_number > $query->offset"
		));
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
	protected function prepareUpdate($table, $data, $where = null, $limit = null)
	{
		$table = $this->identifier($table);
		
		foreach ($data as $key => $value) {
			$column = $this->identifier($key);
			$value = $this->value($value);
			$data[$key] = "$column = $value";
		}
		
		$values = implode(', ', $data);
		
		return static::concatenate(array('UPDATE', $limit, $table, 'SET', $values, $where));
	}
	
	/**
	 * Prepare a DELETE statement with the given table and clauses.
	 * 
	 * @param string $table
	 * @param string $where [optional]
	 * @param string $limit [optional]
	 * @return string
	 */
	protected function prepareDelete($table, $where = null, $limit = null)
	{
		$table = $this->identifier($table);
		
		if ($table == '*' || !$table || !$where) {
			return null;
		}
		
		return static::concatenate(array('DELETE', $limit, 'FROM', $table, $where));
	}
}
