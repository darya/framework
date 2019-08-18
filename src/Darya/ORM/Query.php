<?php

namespace Darya\ORM;

use Darya\Storage;
use InvalidArgumentException;

/**
 * Darya's ORM query.
 *
 * @mixin Storage\Query
 * @property-read string $entity
 * @property-read Storage\Query $storageQuery
 * @property-read string[] $has
 * @property-read string[] $with
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
	public function __construct(string $entity, Storage\Query $storageQuery)
	{
		$this->entity       = $entity;
		$this->storageQuery = $storageQuery;
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
		return $this->resource($entity);
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
}
