<?php
namespace Darya\Database;

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
	 * Query the database and return the result.
	 * 
	 * @param string $query
	 * @return \Darya\Database\Result
	 */
	public function query($query);
	
	/**
	 * Escape a string for use in a query.
	 * 
	 * @param string $string
	 * @return string
	 */
	public function escape($string);
	
	/**
	 * Retrieve any error that occurred with the last operation.
	 * 
	 * @return \Darya\Database\Error
	 */
	public function error();
	
}
