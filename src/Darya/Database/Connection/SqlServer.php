<?php
namespace Darya\Database\Connection;

use Darya\Database\AbstractConnection;
use Darya\Database\Error;
use Darya\Database\Result;

/**
 * Darya's SQL Server (MSSQL) database interface for Windows.
 * 
 * TODO: Insert IDs.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class SqlServer extends AbstractConnection {
	
	/**
	 * @var array Connection details
	 */
	protected $details;
	
	/**
	 * Instantiate a new SQL Server connection with the given credentials.
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
		
		$host = $this->details['host'];
		
		if ($this->details['port']) {
			$host .= ', ' . $this->details['port'];
		}
		
		$this->connection = sqlsrv_connect($this->details['host'], array(
			'UID'      => $this->details['user'],
			'PWD'      => $this->details['pass'],
			'Database' => $this->details['name']
		));
		
		if ($this->error()) {
			return false;
		}
		
		return $this->connected = true;
	}
	
	/**
	 * Close the connection.
	 */
	public function disconnect() {
		if ($this->connected()) {
			sqlsrv_close($this->connection);
		}
		
		$this->connected = false;
	}
	
	/**
	 * Query the database.
	 * 
	 * @param string $sql
	 * @return \Darya\Database\Result
	 */
	public function query($sql) {
		parent::query($sql);
		
		$this->connect();
		
		$mssql_result = sqlsrv_query($this->connection, $sql, array(), array(
			'Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED
		));
		
		$result = array(
			'data'      => array(),
			'fields'    => array(),
			'affected'  => null,
			'num_rows'  => null,
			'insert_id' => null
		);
		
		$error = $this->error();
		
		if ($mssql_result === false || $error) {
			return new Result($sql, array(), array(), $error);
		}
		
		$result['num_rows'] = sqlsrv_num_rows($mssql_result);
		$result['affected'] = sqlsrv_rows_affected($mssql_result);
		
		if ($result['num_rows']) {
			while ($row = sqlsrv_fetch_array($mssql_result, SQLSRV_FETCH_ASSOC)) {
				if (!$result['fields']) {
					$result['fields'] = array_keys($row);
				}
				
				$result['data'][] = $row;
			}
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
	 * Escape the given string for use in a query.
	 * 
	 * @link http://stackoverflow.com/a/574821/1744006
	 * @param string $string
	 * @return string
	 */
	public function escape($string) {
		if (is_numeric($string)) {
			return $string;
		}
		
		$unpacked = unpack('H*hex', $string);
		
		return '0x' . $unpacked['hex'];
	}
	
	/**
	 * Retrieve error information regarding the last operation.
	 * 
	 * Returns null if there is no error.
	 * 
	 * @return Error
	 */
	public function error() {
		$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
		
		if (!$errors) {
			return null;
		}
		
		return new Error($errors[0]['code'], $errors[0]['message']);
	}
}
