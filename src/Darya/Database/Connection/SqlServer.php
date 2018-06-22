<?php
namespace Darya\Database\Connection;

use Darya\Database\AbstractConnection;
use Darya\Database\Error;
use Darya\Database\Query\Translator;
use Darya\Database\Result;
use Darya\Database\Query;

/**
 * Darya's SQL Server (MSSQL) database interface for Windows.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class SqlServer extends AbstractConnection
{
	/**
	 * @var resource
	 */
	protected $connection;

	/**
	 * Initiate the connection.
	 *
	 * @return bool
	 */
	public function connect()
	{
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
	public function disconnect()
	{
		if ($this->connected()) {
			sqlsrv_close($this->connection);
		}

		$this->connected = false;
	}

	/**
	 * Retrieve the query translator.
	 *
	 * @return Translator
	 */
	public function translator()
	{
		if (!$this->translator) {
			$this->translator = new Translator\SqlServer;
		}

		return $this->translator;
	}

	/**
	 * Query the database for the last ID generated, if the given query is
	 * an insert query.
	 *
	 * @param string $query
	 * @return int
	 */
	protected function queryInsertId($query)
	{
		if (!preg_match('/^\s*INSERT\s+INTO\b/i', $query)) {
			return null;
		}

		$result = sqlsrv_query($this->connection, "SELECT CAST(COALESCE(SCOPE_IDENTITY(), @@IDENTITY) as INT) id");
		list($id) = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC);

		return $id;
	}

	/**
	 * Query the database for the number of rows affected by the last query.
	 *
	 * @param string $query
	 * @return int
	 */
	protected function queryAffected($query)
	{
		if (preg_match('/^\s*SELECT\b/i', $query)) {
			return null;
		}

		$result = sqlsrv_query($this->connection, "SELECT @@ROWCOUNT affected");
		list($affected) = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC);

		return $affected;
	}

	/**
	 * Query the database.
	 *
	 * TODO: Simplify.
	 * TODO: Strip out row_number if present.
	 *
	 * @param Query|string $query
	 * @param array        $parameters [optional]
	 * @return Result
	 */
	public function query($query, array $parameters = array())
	{
		if (!$query instanceof Query) {
			$query = new Query($query, $parameters);
		}

		$this->lastResult = null;

		$this->connect();

		if (!$this->connected()) {
			$this->lastResult = new Result($query, array(), array(), $this->error());

			$this->event('sqlserver.query', array($this->lastResult));

			return $this->lastResult;
		}

		$this->event('sqlserver.prequery', array($query));

		$mssql_result = sqlsrv_query($this->connection, $query->string, $query->parameters, array(
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
			$this->lastResult = new Result($query, array(), array(), $error);

			$this->event('sqlserver.query', array($this->lastResult));

			return $this->lastResult;
		}

		$result['num_rows'] = sqlsrv_num_rows($mssql_result);

		if ($result['num_rows']) {
			while ($row = sqlsrv_fetch_array($mssql_result, SQLSRV_FETCH_ASSOC)) {
				if (!$result['fields']) {
					$result['fields'] = array_keys($row);
				}

				$result['data'][] = $row;
			}
		}

		$result['insert_id'] = $this->queryInsertId($query);
		$result['affected'] = $this->queryAffected($query);

		$info = array(
			'count'     => $result['num_rows'],
			'fields'    => $result['fields'],
			'affected'  => $result['affected'],
			'insert_id' => $result['insert_id']
		);

		$this->lastResult = new Result($query, $result['data'], $info, $error);

		$this->event('sqlserver.query', array($this->lastResult));

		return $this->lastResult;
	}

	/**
	 * Retrieve error information regarding the last query or connection
	 * attempt.
	 *
	 * Returns null if there is no error.
	 *
	 * @return Error
	 */
	public function error()
	{
		$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);

		if (!$errors) {
			return null;
		}

		return new Error($errors[0]['code'], $errors[0]['message']);
	}
}
