<?php
namespace Darya\Database\Connection;

use mysqli as php_mysqli;
use mysqli_result;
use Darya\Database\AbstractConnection;
use Darya\Database\Error;
use Darya\Database\Result;

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
	 * @param string $sql
	 * @return Result
	 */
	public function query($sql) {
		parent::query($sql);
		$this->connect();
		$mysqli_result = $this->connection->query($sql);
		
		$result = array(
			'data'      => array(),
			'affected'  => null,
			'fields'    => array(),
			'insert_id' => null,
			'num_rows'  => null
		);
		
		$error = $this->error();
		
		if ($mysqli_result === false || $error) {
			return new Result($sql, array(), array(), $error);
		}
		
		if (is_object($mysqli_result) && $mysqli_result instanceof mysqli_result) {
			$result['data'] = $mysqli_result->fetch_all(MYSQL_ASSOC);
			$result['fields'] = $mysqli_result->fetch_fields();
			$result['num_rows'] = $mysqli_result->num_rows;
		} else {
			$result['data'] = array();
			$result['affected'] = $this->connection->affected_rows;
			$result['insert_id'] = $this->connection->insert_id;
		}
		
		$info = array(
			'count'     => $result['num_rows'],
			'fields'    => $result['fields'],
			'affected'  => $result['affected'],
			'insert_id' => $result['insert_id']
		);
		
		$this->lastResult = new Result($sql, $result['data'], $info, $error);
		
		return $this->lastResult;
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
	 * @return array
	 */
	public function error() {
		if (!$this->connection) {
			return null;
		}
		
		if ($this->connection->connect_errno) {
			return array(
				'number'  => $this->connection->connect_errno,
				'message' => $this->connection->connect_error
			);
		}
		
		if ($this->connection->errno) {
			return array(
				'number'  => $this->connection->errno,
				'message' => $this->connection->error
			);
		}
		
		return null;
	}
	
}
