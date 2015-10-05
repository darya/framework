<?php
namespace Darya\Database\Connection;

use mysqli as php_mysqli;
use mysqli_result;
use mysqli_stmt;
use ReflectionClass;
use Darya\Database\AbstractConnection;
use Darya\Database\Error;
use Darya\Database\Result;
use Darya\Database\Query\Translator;

/**
 * Darya's MySQL database interface. Uses mysqli.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class MySql extends AbstractConnection {
	
	/**
	 * Fetch result data from the given MySQLi statement.
	 * 
	 * Expects the statement to have been executed.
	 * 
	 * Attempts to use mysqli_stmt::get_result() and mysqli_result::fetch_all(),
	 * but falls back to fetching from the statement directly if get_result()
	 * isn't found (mysqlnd isn't installed).
	 * 
	 * @param mysqli_stmt $statement
	 * @return array array($data, $count, $fields)
	 */
	protected function fetchResult(mysqli_stmt $statement)
	{
		if (method_exists($statement, 'get_result')) { // fetch_all will exist
			$result = $statement->get_result();
			
			if (is_object($result) && $result instanceof mysqli_result) {
				return array(
					$result->fetch_all(MYSQL_ASSOC),
					$result->fetch_fields(),
					$result->num_rows
				);
			} else {
				return array(array(), array(), null);
			}
		}
		
		// MySQLi is shit and I should have just used PDO
		$data = array();
		$metadata = $stmt->result_metadata();
		
		$row = array();
		$count = 0;
		$fields = array();
		$arguments = array();
		
		while ($field = $metadata->fetch_field()) {
			$fields[] = (array) $field;
			$arguments[] = &$row[$field->name];
		}
		
		if ($statement->num_rows > 0) {
			call_user_func_array(array($stmt, 'bind_result'), $arguments);
			
			while ($statement->fetch()) {
				$data[] = array_combine(array_keys($row), array_values($row));
				$count++;
			}
		}
		
		return array($data, $fields, $count);
	}
	
	/**
	 * Retrieve the type of a variable for binding mysqli parameters.
	 * 
	 * @param mixed $parameter
	 * @return string
	 */
	protected function prepareType($parameter) {
		if (is_int($parameter)) {
			return 'i';
		}
		
		if (is_float($parameter)) {
			return 'd';
		}
		
		return 's';
	}
	
	/**
	 * Prepares an array of values as an array of references to those values.
	 * 
	 * Required for PHP 5.3+ to prevent warnings when dynamically invoking
	 * mysqli_stmt::bind_param().
	 * 
	 * @param array $parameters
	 * @return array
	 */
	protected function prepareReferences(array $parameters) {
		$references = array();
		
		foreach ($parameters as $key => $value) {
			$references[$key] = &$parameters[$key];
		}
		
		return $references;
	}
	
	/**
	 * Prepare the given query and parameters as a mysqli statement.
	 * 
	 * @param string $query
	 * @param array  $parameters [optional]
	 * @return \mysqli_stmt
	 */
	protected function prepareStatement($query, $parameters = array()) {
		$statement = $this->connection->stmt_init();
		
		if (!$statement->prepare($query)) {
			return $statement;
		}
		
		if (empty($parameters)) {
			return $statement;
		}
		
		$types = '';
		
		foreach ((array) $parameters as $parameter) {
			$types .= $this->prepareType($parameter);
		}
		
		array_unshift($parameters, $types);
		
		call_user_func_array(
			array($statement, 'bind_param'),
			$this->prepareReferences($parameters)
		);
		
		return $statement;
	}
	
	/**
	 * Prepare a result array using the given mysqli statement.
	 * 
	 * @param mysqli_stmt $statement
	 * @return array
	 */
	protected function prepareStatementResult(mysqli_stmt $statement) {
		list($data, $fields, $count) = $this->fetchResult($statement);
		
		$result = array(
			'data'      => $data,
			'fields'    => $fields,
			'affected'  => $statement->affected_rows,
			'num_rows'  => $count,
			'insert_id' => $statement->insert_id
		);
		
		return $result;
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
	 * Retrieve the query translator.
	 * 
	 * @return Translator
	 */
	public function translator() {
		if (!$this->translator) {
			$this->translator = new Translator\MySql;
		}
		
		return $this->translator;
	}
	
	/**
	 * Query the database with the given query and optional parameters.
	 * 
	 * @param string $query
	 * @param array  $parameters [optional]
	 * @return Result
	 */
	public function query($query, array $parameters = array()) {
		$this->connect();
		
		$this->event('mysql.prequery', array($query, $parameters));
		
		$statement = $this->prepareStatement($query, $parameters);
		
		if ($statement->errno) {
			$error = new Error($statement->errno, $statement->error);
			$this->lastResult = new Result($query, $parameters, array(), array(), $error);
			
			return $this->lastResult;
		}
		
		$statement->execute();
		// $statement->store_result(); // May not need this
		
		$error = $this->error();
		
		if ($error) {
			$this->lastResult = new Result($query, $parameters, array(), array(), $error);
			
			return $this->lastResult;
		}
		
		$result = $this->prepareStatementResult($statement);
		
		$statement->close();
		
		$info = array(
			'count'     => $result['num_rows'],
			'fields'    => $result['fields'],
			'affected'  => $result['affected'],
			'insert_id' => $result['insert_id']
		);
		
		$this->lastResult = new Result($query, $parameters, $result['data'], $info, $error);
		
		$this->event('mysql.query', array($this->lastResult));
		
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
	 * Retrieve error information regarding the last query or connection
	 * attempt.
	 * 
	 * Returns null if there is no error.
	 * 
	 * @return Error
	 */
	public function error() {
		$connectionError = $this->connectionError();
		
		if ($connectionError) {
			return $connectionError;
		}
		
		if ($this->lastResult && $this->lastResult->error) {
			return $this->lastResult->error;
		}
		
		return null;
	}
	
	/**
	 * Retrieve error information from the mysqli connection object.
	 * 
	 * Checks for general errors first, then connection errors.
	 * 
	 * Returns null if there is no error.
	 * 
	 * @return Error
	 */
	protected function connectionError() {
		if (!$this->connection) {
			return null;
		}
		
		if ($this->connection->errno) {
			return new Error($this->connection->errno, $this->connection->error);
		}
		
		if ($this->connection->connect_errno) {
			return new Error($this->connection->connect_errno, $this->connection->connect_error);
		}
		
		return null;
	}
	
}
