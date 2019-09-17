<?php

namespace Darya\Storage\Query;

use Darya\Storage\Query;
use Darya\Storage\Queryable;
use Darya\Storage\Result;

/**
 * Darya's storage query builder.
 *
 * Forwards method calls to a storage query and executes it on the given
 * queryable storage interface once the query has been built.
 *
 * TODO: Implement event dispatcher awareness.
 * TODO: Pre-query callback, rename callback to resultCallback.
 *
 * @mixin Query
 * @property-read Query     $query
 * @property-read Queryable $storage
 * @property-read callable  $callback
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Builder
{
	/**
	 * The query to execute.
	 *
	 * @var Query
	 */
	protected $query;

	/**
	 * The storage interface to query.
	 *
	 * @var Queryable
	 */
	protected $storage;

	/**
	 * A callback that processes the result of the query.
	 *
	 * @var callback
	 */
	protected $callback;

	/**
	 * Query methods that should trigger query execution.
	 *
	 * @var array
	 */
	protected static $executors = [
		'all', 'read', 'select', 'unique', 'distinct', 'delete'
	];

	/**
	 * Instantiate a new query builder for the given query and storage.
	 *
	 * @param Query     $query
	 * @param Queryable $storage
	 */
	public function __construct(Query $query, Queryable $storage)
	{
		$this->query   = $query;
		$this->storage = $storage;
	}

	/**
	 * Dynamically invoke methods to fluently build a query.
	 *
	 * If the method is an execute method, the query is executed and the result
	 * returned.
	 *
	 * @param string $method
	 * @param array  $arguments
	 * @return $this|Result
	 */
	public function __call($method, $arguments)
	{
		$this->query->$method(...$arguments);

		if (in_array($method, static::$executors)) {
			return $this->run();
		}

		return $this;
	}

	/**
	 * Dynamically retrieve a property.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		if (!property_exists($this, $property)) {
			return $this->query->$property;
		}

		return $this->$property;
	}

	/**
	 * Set a callback to run on results before returning them.
	 *
	 * Callbacks should accept one parameter: the query result.
	 *
	 * @param callback $callback
	 */
	public function callback($callback)
	{
		$this->callback = $callback;
	}

	/**
	 * Run the query through the storage interface.
	 *
	 * Always returns a Result object, ignoring the callback.
	 *
	 * @return Result
	 */
	public function execute()
	{
		return $this->storage->run($this->query);
	}

	/**
	 * Run the query through the storage interface.
	 *
	 * Returns the return value of the callback, if one is set.
	 *
	 * @return Result|mixed The `Result` of the query, or the return of the callback if one is set.
	 */
	public function run()
	{
		$result = $this->storage->run($this->query);

		if (!is_callable($this->callback)) {
			return $result;
		}

		return call_user_func($this->callback, $result);
	}

	/**
	 * Alias for the `run()` method.
	 *
	 * @return mixed
	 * @see Builder::run()
	 */
	public function cheers()
	{
		return $this->run();
	}
}
