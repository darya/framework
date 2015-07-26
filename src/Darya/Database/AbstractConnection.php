<?php
namespace Darya\Database;

use Darya\Database\Connection;

/**
 * Darya's abstract database connection.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class AbstractConnection implements Connection {
	
	/**
	 * @var mixed Connection object or link identifier
	 */
	protected $connection;
	
	/**
	 * @var bool Whether the connection is currently active
	 */
	protected $connected;
	
	/**
	 * @var array Connection details
	 */
	protected $details;
	
	/**
	 * @var \Darya\Database\Result Result of the last query
	 */
	protected $lastResult;
	
	/**
	 * Instantiate a new SQL Server connection with the given credentials.
	 * 
	 * The connection is not made upon instantiating the object, but instead
	 * after using either the `connect()` or `query()` methods.
	 * 
	 * @param string $host Hostname to connect to
	 * @param string $user Username to login with
	 * @param string $pass Password to login with
	 * @param string $name Database to select
	 * @param int    $port [optional] Port to connect to
	 */
	public function __construct($host, $user, $pass, $name, $port = null) {
		$this->details = array(
			'host' => $host,
			'user' => $user,
			'pass' => $pass,
			'name' => $name,
			'port' => $port
		);
	}
	
	/**
	 * Determine whether there is an active connection to the database.
	 * 
	 * @return bool
	 */
	public function connected() {
		return $this->connected;
	}
	
	/**
	 * Query the database.
	 * 
	 * @param string $query
	 * @return \Darya\Database\Result
	 */
	public function query($query) {
		$this->lastQuery = $query;
	}
	
	/**
	 * Get the error produced by the last query, if any.
	 * 
	 * @return \Darya\Database\Error
	 */
	public function error() {
		return $this->lastResult->error;
	}
	
	/**
	 * Get the last query made by this connection.
	 * 
	 * @return string Database query
	 */
	public function lastQuery() {
		return $this->lastResult->query;
	}
	
	/**
	 * Get a detailed result array for the last query made by this connection.
	 * 
	 * @return array
	 */
	public function lastResult() {
		return $this->lastResult;
	}
	
}
