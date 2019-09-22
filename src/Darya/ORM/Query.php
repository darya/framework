<?php

namespace Darya\ORM;

use Darya\Storage;

/**
 * Darya's ORM query.
 *
 * @property-read string              $entity
 * @property-read string[]|callable[] $has
 * @property-read string[]|callable[] $with
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Query extends Storage\Query
{
	/**
	 * The mapper used for this ORM query.
	 *
	 * @var Mapper
	 */
	protected $mapper;

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
	 * @param Mapper $mapper
	 */
	public function __construct(Mapper $mapper)
	{
		parent::__construct($mapper->getEntityMap()->getResource());

		$this->mapper = $mapper;
	}

	public function __get(string $property)
	{
		if ($property === 'entity') {
			return $this->mapper->getEntityMap()->getName();
		}

		return parent::__get($property);
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
