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
	 * @var mixed Connection object
	 */
	protected $connection;
	
	/**
	 * @var \Darya\Database\Result Detailed result array corresponding to the last query
	 */
	protected $lastResult;
	
	/**
	 * Instantiate a new Database object and initialise its connection.
	 * 
	 * @param string $host Hostname to connect to
	 * @param string $user Username to authenticate with
	 * @param string $pass Password to authenticate with
	 * @param string $name Database to select
	 * @param int    $port [optional] Port to connect to
	 */
	abstract public function __construct($host, $user, $pass, $name, $port = null);
	
	/**
	 * Query the database and return any resulting data.
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
