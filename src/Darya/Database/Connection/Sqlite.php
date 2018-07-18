<?php
namespace Darya\Database\Connection;

use PDO;
use Darya\Database\AbstractConnection;
use Darya\Database\Error;
use Darya\Database\Result;
use Darya\Database\Query;
use Darya\Database\Query\Translator;

/**
 * Darya's SQLite database interface. Uses PDO.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Sqlite extends AbstractConnection
{
	/**
	 * Connection object.
	 *
	 * @var PDO
	 */
	protected $connection;

	/**
	 * Create a new SQLite database connection.
	 *
	 * Creates an in-memory database if no path is given.
	 *
	 * @param string $path    [optional] The path to the SQLite database file
	 * @param array  $options [optional] Options for the SQLite PDO connection
	 */
	public function __construct($path = null, array $options = array())
	{
		$this->details['path'] = $path ?: ':memory:';
		$this->options = array_merge(array(
			'persistent' => false
		), $options);
	}

	/**
	 * Initiate the connection.
	 */
	public function connect()
	{
		if ($this->connected()) {
			return true;
		}

		$path = $this->details['path'];

		$this->connection = new PDO("sqlite:{$path}", null, null, array(
			PDO::ATTR_PERSISTENT => $this->options['persistent']
		));

		if ($this->connection->errorCode()) {
			return false;
		}

		$this->connected = true;
	}

	/**
	 * Close the connection.
	 */
	public function disconnect()
	{
		$this->connection = null;
	}

	/**
	 * Retrieve the query translator.
	 *
	 * @return Translator
	 */
	public function translator()
	{
		if (!$this->translator) {
			$this->translator = new Translator\MySql;
		}

		return $this->translator;
	}

	/**
	 * Query the database.
	 *
	 * @param Query|string $query
	 * @param array        $parameters [optional]
	 * @return Result
	 */
	public function query($query, array $parameters = array())
	{
		if (!($query instanceof Query)) {
			$query = new Query((string) $query, $parameters);
		}

		$this->lastResult = null;

		$this->connect();

		$statement = $this->connection->prepare($query->string);

		$error = $this->error();

		if ($error) {
			$this->lastResult = new Result($query, array(), array(), $error);

			return $this->lastResult;
		}

		$statement->execute($query->parameters);

		if ($statement->errorCode()) {
			$error = new Error($statement->errorCode(), $statement->errorInfo()[2]);
			$this->lastResult = new Result($query, array(), array(), $error);

			return $this->lastResult;
		}

		$data = $statement->fetchAll(PDO::FETCH_ASSOC);

		$info = array(
			'count' => $statement->rowCount(),
		);

		$this->lastResult = new Result($query, $data, $info);

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
	 * Retrieve error information from the PDO connection object.
	 *
	 * Returns null if there is no error.
	 *
	 * @return Error
	 */
	protected function connectionError()
	{
		if (!$this->connection) {
			return null;
		}

		$errorInfo = $this->connection->errorInfo();

		if (empty($errorInfo[1])) {
			return null;
		}

		return new Error($errorInfo[1], $errorInfo[2]);
	}
}
