<?php
namespace Darya\Database;

use Darya\Events\Dispatchable;
use Darya\Storage\Query as StorageQuery;

/**
 * Darya's abstract database connection.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class AbstractConnection implements Connection
{
	/**
	 * Connection object or link identifier.
	 *
	 * @var mixed
	 */
	protected $connection;

	/**
	 * Whether the connection is currently active.
	 *
	 * @var bool
	 */
	protected $connected = false;

	/**
	 * Connection details.
	 *
	 * @var array
	 */
	protected $details = array();

	/**
	 * Connection options.
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * The event dispatcher.
	 *
	 * @var Dispatchable
	 */
	protected $eventDispatcher;

	/**
	 * The result of the last query.
	 *
	 * @var \Darya\Database\Result
	 */
	protected $lastResult;

	/**
	 * The storage query translator.
	 *
	 * @var \Darya\Database\Query\Translator
	 */
	protected $translator;

	/**
	 * Instantiate a new database connection with the given credentials.
	 *
	 * The connection is not made upon instantiating the object, but instead
	 * after using either the `connect()` or `query()` methods.
	 *
	 * @param string $host Hostname to connect to
	 * @param string $user Username to login with
	 * @param string $pass Password to login with
	 * @param string $name Database to use
	 * @param int    $port [optional] Port to connect to
	 */
	public function __construct($host, $user, $pass, $name, $port = null)
	{
		$this->details = array(
			'host' => $host,
			'user' => $user,
			'pass' => $pass,
			'name' => $name,
			'port' => $port
		);
	}

	/**
	 * Set an event dispatcher for the connection to use.
	 *
	 * @param Dispatchable $dispatcher
	 */
	public function setEventDispatcher(Dispatchable $dispatcher)
	{
		$this->eventDispatcher = $dispatcher;
	}

	/**
	 * Helper method for dispatching events. Silent if an event dispatcher is
	 * not set.
	 *
	 * @param string $name
	 * @param mixed  $arguments [optional]
	 * @return array
	 */
	protected function event($name, array $arguments = array())
	{
		if ($this->eventDispatcher) {
			return $this->eventDispatcher->dispatch($name, $arguments);
		}

		return array();
	}

	/**
	 * Determine whether there is an active connection to the database.
	 *
	 * @return bool
	 */
	public function connected()
	{
		return $this->connected;
	}

	/**
	 * Retrieve the query translator to use for this connection.
	 *
	 * @return \Darya\Database\Query\Translator
	 */
	abstract public function translator();

	/**
	 * Translate a storage query to a database query for this connection.
	 *
	 * @param StorageQuery $storageQuery
	 * @return \Darya\Database\Query
	 */
	public function translate(StorageQuery $storageQuery)
	{
		return $this->translator()->translate($storageQuery);
	}

	/**
	 * Get the error produced by the last query, if any.
	 *
	 * @return \Darya\Database\Error
	 */
	public function error()
	{
		return $this->lastResult->error;
	}

	/**
	 * Get a detailed result array for the last query made by this connection.
	 *
	 * @return \Darya\Database\Result
	 */
	public function lastResult()
	{
		return $this->lastResult;
	}
}
