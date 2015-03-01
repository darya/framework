<?php
namespace Darya\Database;

use \mysqli as php_mysqli;

/**
 * Darya's MySQL database interface. Uses mysqli.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class MySQLi extends \Darya\Database\AbstractDatabase {
	
	/**
	 * @var bool
	 */
	protected $connected;
	
	/**
	 * @var array
	 */
	protected $operators = array('>=','<=','>','<','=','!=','<>','IN','NOT IN','IS','IS NOT','LIKE','NOT LIKE');
	
	public function __construct($host, $user, $pass, $name, $port = null) {
		$this->connect($host, $user, $pass, $name, $port);
	}
	
	public function connect($host, $user, $pass, $name, $port = null) {
		$this->connection = new php_mysqli($host, $user, $pass, $name, $port);
		
		if ($this->connection->connect_errno) {
			echo "MySQLi connection failed: (" . $this->connection->connect_errno . ") " . $this->connection->connect_error;
			return false;
		}
		
		return $this->connected = true;
	}
	
	public function connected() {
		return $this->connected && !$this->connection->connect_errno;
	}
	
	public function query($sql, $verbose = false) {
		parent::query($sql, $verbose);
		$result = $this->connection->query($sql);
		
		if ($error = $this->error()) {
			echo $error['error'] . '<br/>SQL: ' . $sql;
			
			return array();
		}
		
		if ($result) {
			if (get_class($result) == 'mysqli_result') {
				$this->lastResult = array(
					'data' => $result->fetch_all(MYSQL_ASSOC),
					'fields' => $result->fetch_fields(),
					'num_rows' => $result->num_rows
				);
			} else {
				$this->lastResult = array(
					'data' => array(),
					'insert_id' => $this->connection->insert_id,
					'affected' => $this->connection->affected_rows
				);
			}
			
			return $verbose ? $this->lastResult : $this->lastResult['data'];
		}
	}
	
	/**
	 * Escape the given string for a MySQL query.
	 * 
	 * @param string $string
	 * @return string
	 */
	public function escape($string) {
		return $this->connection->real_escape_string($string);
	}
	
	/**
	 * Return error information as an array.
	 * 
	 * The returned array will have the keys 'errno' and 'error'. Returns false
	 * if there is no error.
	 * 
	 * @return array|false
	 */
	public function error() {
		return $this->connection->errno ? array('errno' => $this->connection->errno, 'error' => $this->connection->error) : false;
	}
}
