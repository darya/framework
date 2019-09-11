<?php

namespace Darya\ORM\Query;

use Darya\ORM\EntityManager;
use Darya\ORM\Query;

/**
 * Darya's ORM query builder.
 *
 * TODO: If ORM\Query extended Storage\Query and ORM\EntityManager implemented Storage\Queryable,
 *       this class is no longer needed; a Storage\Query\Builder could be used with both Storage and EntityManager.
 *       Right now, this would be good, because this class is almost a line-for-line duplicate of Storage\Query\Builder.
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
	private $query;

	/**
	 * The entity manager to query.
	 *
	 * @var EntityManager
	 */
	private $orm;

	/**
	 * A callback that processes resulting entities of the query.
	 *
	 * @var callback
	 */
	protected $callback;

	/**
	 * Create a new ORM query builder.
	 *
	 * @param Query         $query
	 * @param EntityManager $orm
	 */
	public function __construct(Query $query, EntityManager $orm)
	{
		$this->query = $query;
		$this->orm   = $orm;
	}

	/**
	 * Dynamically invoke methods to fluently build a query.
	 *
	 * If the method is an execute method, the query is executed and the result
	 * returned.
	 *
	 * @param string $method
	 * @param array  $arguments
	 * @return $this|object[]
	 */
	public function __call($method, $arguments)
	{
		call_user_func_array([$this->query, $method], $arguments);

//		if (in_array($method, static::$executors)) {
//			return $this->run();
//		}

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
	 * Run the query through the entity manager.
	 *
	 * Always returns entities directly from the manager, ignoring the callback.
	 *
	 * @return object[]
	 */
	public function execute()
	{
		return $this->orm->run($this->query);
	}

	/**
	 * Run the query through the storage interface.
	 *
	 * Returns the return value of the callback, if one is set.
	 *
	 * @return object[]|mixed The resulting entities of the query, or the return of the callback if one is set.
	 */
	public function run()
	{
		$result = $this->orm->run($this->query);

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
