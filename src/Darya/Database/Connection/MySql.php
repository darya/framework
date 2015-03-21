<?php
namespace Darya\Database\Connection;

use mysqli as php_mysqli;
use mysqli_result;
use Darya\Database\AbstractConnection;

/**
 * Darya's MySQL database interface. Uses mysqli.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class MySql extends AbstractConnection {
	
	/**
	 * @var bool Whether the connection is currently active
	 */
	protected $connected;
	
	/**
	 * @var array Connection details
	 */
	protected $details;
	
	/**
	 * Instantiate a new MySQL connection with the given credentials.
	 * 
	 * The connection is not made upon instantiating the object, but instead
	 * after using either the `connect()` or `query()` methods.
	 * 
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 * @param string $name
	 * @param int    $port [optional]
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
	 * Initiate the connection.
	 * 
	 * @return bool
	 */
	public function connect() {
		if ($this->connected()) {
			return true;
		}
		
		$this->connection = new php_mysqli(
			$this->details['host'],
			$this->details['user'],
			$this->details['pass'],
			$this->details['name'],
			$this->details['port']
		);
		
		if ($this->connection->connect_errno) {
			return false;
		}
		
		return $this->connected = true;
	}
	
	/**
	 * Determine whether the connection is currently active.
	 * 
	 * @return bool
	 */
	public function connected() {
		return $this->connected && !$this->connection->connect_errno;
	}
	
	/**
	 * Close the connection.
	 */
	public function disconnect() {
		$this->connection->close();
		$this->connected = false;
	}
	
	/**
	 * Query the database.
	 * 
	 * Returns an associative array of data if the query retrieves any data.
	 * Expect an empty away otherwise, unless verbose is true.
	 * 
	 * If verbose is true, expect the keys 'data', 'affected', 'fields',
	 * 'insert_id' and 'num_rows'.
	 * 
	 * Reads set 'data', 'fields', and 'num_rows'. Writes set 'insert_id' and
	 * 'affected'.
	 * 
	 * @param string $sql
	 * @param bool   $verbose
	 * @return array
	 */
	public function query($sql, $verbose = false) {
		parent::query($sql, $verbose);
		$this->connect();
		$result = $this->connection->query($sql);
		
		if ($result === false || $this->error()) {
			return array();
		}
		
		$lastResult = array(
			'data'      => array(),
			'affected'  => null,
			'fields'    => array(),
			'insert_id' => null,
			'num_rows'  => null,
		);
		
		if (is_object($result) && $result instanceof mysqli_result) {
			$lastResult['data'] = $result->fetch_all(MYSQL_ASSOC);
			$lastResult['fields'] = $result->fetch_fields();
			$lastResult['num_rows'] = $result->num_rows;
		} else {
			$lastResult['data'] = array();
			$lastResult['affected'] = $this->connection->affected_rows;
			$lastResult['insert_id'] = $this->connection->insert_id;
		}
		
		$this->lastResult = $lastResult;
		
		return $verbose ? $this->lastResult : $this->lastResult['data'];
	}
	
	/**
	 * Escape the given string for a MySQL query.
	 * 
	 * @param string $string
	 * @return string
	 */
	public function escape($string) {
		$this->connect();
		return $this->connection->real_escape_string($string);
	}
	
	/**
	 * Retrieve error information regarding the last operation.
	 * 
	 * The returned array will have the keys 'errno', 'error' and 'query'.
	 * Returns false if there is no error.
	 * 
	 * @return array|bool
	 */
	public function error() {
		if (!$this->connection) {
			return false;
		}
		
		if ($this->connection->connect_errno) {
			return array(
				'errno' => $this->connection->connect_errno,
				'error' => $this->connection->connect_error,
				'query' => null
			);
		}
		
		if ($this->connection->errno) {
			return array(
				'errno' => $this->connection->errno,
				'error' => $this->connection->error,
				'query' => $this->lastQuery
			);
		}
		
		return false;
	}
	
}
