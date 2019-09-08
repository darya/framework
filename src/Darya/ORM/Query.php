<?php

namespace Darya\ORM;

use Darya\Storage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Darya's ORM query.
 *
 * @mixin Storage\Query
 * @property-read string              $entity
 * @property-read Storage\Query       $storageQuery
 * @property-read string[]|callable[] $has
 * @property-read string[]|callable[] $with
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Query
{
	/**
	 * The entity to query.
	 *
	 * @var string
	 */
	protected $entity;

	/**
	 * The underlying storage query.
	 *
	 * @var Storage\Query
	 */
	protected $storageQuery;

	/**
	 * Relationships to check existence for.
	 *
	 * @var string[]
	 */
	protected $has = [];

	/**
	 * Relationships to load entities with.
	 *
	 * @var string[]
	 */
	protected $with = [];

	/**
	 * Query constructor.
	 *
	 * @param string        $entity
	 * @param Storage\Query $storageQuery
	 */
	public function __construct(Storage\Query $storageQuery, string $entity)
	{
		$this->storageQuery = $storageQuery;
		$this->entity($entity);
	}

	/**
	 * Set the entity to query.
	 *
	 * Alias for resource().
	 *
	 * @param string $entity
	 * @return Query
	 * @see \Darya\Storage\Query::resource()
	 */
	public function entity(string $entity)
	{
		$this->entity = $entity;

		return $this;
	}

	/**
	 * Set the relationships to check existence for.
	 *
	 * @param array $relationships
	 * @return $this
	 */
	public function has(array $relationships): Query
	{
		$this->has = $relationships;

		return $this;
	}

	/**
	 * Set the relationships to load entities with.
	 *
	 * @param string[] $relationships
	 * @return $this
	 */
	public function with(array $relationships): Query
	{
		$this->with = $relationships;

		return $this;
	}

	/**
	 * Dynamically retrieve a property.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get(string $property)
	{
		if (property_exists($this, $property)) {
			return $this->$property;
		}

		return $this->storageQuery->$property;
	}

	/**
	 * Dynamically invoke a method.
	 *
	 * @param string $method
	 * @param array  $arguments
	 * @return mixed
	 */
	public function __call(string $method, array $arguments)
	{
		if (!method_exists($this->storageQuery, $method)) {
			throw new RuntimeException("Undefined method $method()");
		}

		$result = $this->storageQuery->{$method}(...$arguments);

		if ($result instanceof Storage\Query) {
			return $this;
		}

		return $result;
	}
}
