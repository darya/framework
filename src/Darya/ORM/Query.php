<?php

namespace Darya\ORM;

use Darya\Storage;

/**
 * Darya's ORM query.
 *
 * @property-read string              $entity
 * @property-read Storage\Query       $storageQuery
 * @property-read string[]|callable[] $has
 * @property-read string[]|callable[] $with
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Query extends Storage\Query
{
	/**
	 * The entity to query.
	 *
	 * @var string
	 */
	protected $entity;

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
	 * Create a new ORM query.
	 *
	 * TODO: Should this just have a Mapper instance, or is that overkill?
	 *
	 * @param string $entity   The entity to query.
	 * @param string $resource The resource to query.
	 */
	public function __construct(string $entity, string $resource = '')
	{
		parent::__construct($resource);

		$this->entity($entity);
	}

	/**
	 * Set the entity to query.
	 *
	 * @param string $entity
	 * @return Query
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
}
