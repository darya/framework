<?php
namespace Darya\Database;

use Darya\Storage\Query as StorageQuery;

/**
 * Darya's database connection interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Connection {
	
	/**
	 * Initiate a connection to the database.
	 */
	public function connect();
	
	/**
	 * Determine whether there is an active connection to the database.
	 * 
	 * @return bool
	 */
	public function connected();
	
	/**
	 * Close the connection to the database.
	 */
	public function disconnect();
	
	/**
	 * Translate a storage query to a database query for this connection.
	 * 
	 * @param StorageQuery $storageQuery
	 * @return \Darya\Database\Query
	 */
	public function translate(StorageQuery $storageQuery);
	
	/**
	 * Query the database.
	 * 
	 * @param string $query
	 * @param array  $parameters [optional]
	 * @return \Darya\Database\Result
	 */
	public function query($query, array $parameters = array());
	
	/**
	 * Retrieve any error that occurred with the last operation.
	 * 
	 * @return \Darya\Database\Error
	 */
	public function error();
	
}
