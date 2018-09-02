<?php
namespace Darya\Database;

use Darya\Database\Storage\Query as DatabaseStorageQuery;
use Darya\Database\Storage\Result as DatabaseStorageResult;
use Darya\Storage\Aggregational;
use Darya\Storage\Readable;
use Darya\Storage\Modifiable;
use Darya\Storage\Queryable;
use Darya\Storage\Searchable;
use Darya\Storage\Query as StorageQuery;
use Darya\Storage\Query\Builder as QueryBuilder;

/**
 * Darya's database storage implementation.
 *
 * TODO: Remove listing and add $columns parameter to read().
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Storage implements Aggregational, Readable, Modifiable, Queryable, Searchable
{
	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Instantiate a database-driven data store.
	 *
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Flatten the given data to the values of the given key.
	 *
	 * @param array  $data
	 * @param string $key
	 * @return array
	 */
	protected static function flatten(array $data, $key)
	{
		$flat = array();

		foreach ($data as $row) {
			if (isset($row[$key])) {
				$flat[] = $row[$key];
			}
		}

		return $flat;
	}

	/**
	 * Prepare the given order value as an array.
	 *
	 * @param array|string $order
	 * @return array
	 */
	protected static function prepareOrder($order)
	{
		if (is_array($order)) {
			return $order;
		}

		if (!is_string($order)) {
			return array();
		}

		return array($order => 'asc');
	}

	/**
	 * Retrieve the distinct values of the given database column.
	 *
	 * Returns a flat array of values.
	 *
	 * @param string $table
	 * @param string $column
	 * @param array  $filter [optional]
	 * @param array  $order  [optional]
	 * @param int    $limit  [optional]
	 * @param int    $offset [optional]
	 * @return array
	 */
	public function distinct($table, $column, array $filter = array(), $order = array(), $limit = 0, $offset = 0)
	{
		$query = new StorageQuery($table, array($column), $filter, static::prepareOrder($order), $limit, $offset);
		$query->distinct();

		return static::flatten($this->run($query)->data, $column);
	}

	/**
	 * Retrieve all values of the given database columns.
	 *
	 * Returns an array of associative arrays.
	 *
	 * @param string       $table
	 * @param array|string $columns
	 * @param array        $filter [optional]
	 * @param array|string $order  [optional]
	 * @param int          $limit  [optional]
	 * @param int          $offset [optional]
	 * @return array
	 */
	public function listing($table, $columns, array $filter = array(), $order = array(), $limit = null, $offset = 0)
	{
		$query = new StorageQuery($table, (array) $columns, $filter, static::prepareOrder($order), $limit, $offset);

		return $this->run($query)->data;
	}

	/**
	 * Retrieve database rows that match the given criteria.
	 *
	 * Returns an array of associative arrays.
	 *
	 * @param string       $table
	 * @param array        $filter [optional]
	 * @param array|string $order  [optional]
	 * @param int          $limit  [optional]
	 * @param int          $offset [optional]
	 * @return array
	 */
	public function read($table, array $filter = array(), $order = array(), $limit = null, $offset = 0)
	{
		$query = new StorageQuery($table, array(), $filter, static::prepareOrder($order), $limit, $offset);

		return $this->run($query)->data;
	}

	/**
	 * Count the rows that match the given criteria.
	 *
	 * @param string $table
	 * @param array  $filter [optional]
	 * @return int
	 */
	public function count($table, array $filter = array())
	{
		$query = new StorageQuery($table, array(1), $filter);

		return $this->run($query)->count;
	}

	/**
	 * Insert a record with the given data into the given table.
	 *
	 * Returns the ID of the new row or false if an error occurred.
	 *
	 * @param string $table
	 * @param array  $data
	 * @return int
	 */
	public function create($table, $data)
	{
		$query = new StorageQuery($table);
		$query->create($data);

		return $this->run($query)->insertId;
	}

	/**
	 * Update rows that match the given criteria using the given data.
	 *
	 * Returns the number of rows affected by the update operation.
	 *
	 * If empty data is provided, the query is not run and the call returns 0.
	 *
	 * @param string $table
	 * @param array  $data
	 * @param array  $filter [optional]
	 * @param int    $limit  [optional]
	 * @return int
	 */
	public function update($table, $data, array $filter = array(), $limit = null)
	{
		if (!$data) {
			return 0;
		}

		$query = new StorageQuery($table, array(), $filter, array(), $limit);
		$query->update($data);

		$result = $this->run($query);

		return $result->affected;
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
	public function delete($table, array $filter = array(), $limit = null)
	{
		if ($table == '*' || empty($table) || empty($filter)) {
			return 0;
		}

		$query = new StorageQuery($table, array(), $filter, array(), $limit);
		$query->delete();

		$result = $this->run($query);

		return $result->affected;
	}

	/**
	 * Execute the given storage query.
	 *
	 * @param StorageQuery $storageQuery
	 * @return DatabaseStorageResult
	 */
	public function run(StorageQuery $storageQuery)
	{
		$query = $this->connection->translate($storageQuery);

		$databaseResult = $this->connection->query($query->string, $query->parameters);

		return DatabaseStorageResult::createWithDatabaseResult($storageQuery, $databaseResult);
	}

	/**
	 * Open a query on the given resource.
	 *
	 * Optionally accepts the column(s) to retrieve in the case of a read query.
	 *
	 * @param string       $resource
	 * @param array|string $columns  [optional]
	 * @return QueryBuilder
	 */
	public function query($resource, $columns = array())
	{
		$query = new DatabaseStorageQuery($resource, (array) $columns);

		return new QueryBuilder($query, $this);
	}

	/**
	 * Search for rows in the given table with fields that match the given query
	 * and criteria.
	 *
	 * @param string       $table
	 * @param string       $query
	 * @param array|string $columns [optional]
	 * @param array        $filter  [optional]
	 * @param array|string $order   [optional]
	 * @param int          $limit   [optional]
	 * @param int          $offset  [optional]
	 * @return array
	 */
	public function search($table, $query, $columns = array(), array $filter = array(), $order = array(), $limit = null, $offset = 0)
	{
		$order = static::prepareOrder($order);

		if (!is_string($query) || empty($columns)) {
			return $this->read($table, $filter, $order, $limit, $offset);
		}

		$columns = (array) $columns;
		$search = array('or' => array());

		foreach ($columns as $column) {
			$search['or']["$column like"] = "%$query%";
		}

		$filter = array_merge($filter, $search);

		$query = new StorageQuery($table, array(), $filter, $order, $limit, $offset);

		return $this->run($query)->data;
	}

	/**
	 * Retrieve the error that occurred with the last operation.
	 *
	 * Returns false if there was no error.
	 *
	 * @return string|bool
	 */
	public function error()
	{
		if ($error = $this->connection->error()) {
			return $error->message;
		}

		return false;
	}
}
