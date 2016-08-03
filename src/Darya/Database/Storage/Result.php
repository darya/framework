<?php
namespace Darya\Database\Storage;

use Darya\Database\Query as DatabaseQuery;
use Darya\Database\Result as DatabaseResult;
use Darya\Storage\Query as StorageQuery;
use Darya\Storage\Result as StorageResult;

/**
 * Storage result specific to working with database storage.
 * 
 * @property DatabaseQuery $databaseQuery Database query produced by the storage query that produced this result
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Result extends StorageResult
{
	/**
	 * @var DatabaseQuery
	 */
	protected $databaseQuery;
	
	/**
	 * Create a new database storage result from the given storage query and
	 * attach the given database connection result.
	 * 
	 * @param StorageQuery   $query
	 * @param DatabaseResult $result
	 * @return StorageResult
	 */
	public static function createWithDatabaseResult(StorageQuery $query, DatabaseResult $result)
	{
		$instance = new static($query, $result->data, $result->getInfo(), $result->error);
		
		$instance->setDatabaseQuery($result->query);
		
		return $instance;
	}
	
	/**
	 * Set the database query of the result.
	 * 
	 * @param DatabaseQuery $query
	 */
	public function setDatabaseQuery(DatabaseQuery $query)
	{
		$this->databaseQuery = $query;
	}
}
