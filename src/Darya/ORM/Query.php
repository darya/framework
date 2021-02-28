<?php

namespace Darya\ORM;

use Darya\Storage;

/**
 * Darya's ORM query.
 *
 * @property-read Mapper              $mapper
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
	protected Mapper $mapper;

	/**
	 * Relationships to check existence for.
	 *
	 * @var callable[]|string[]
	 */
	protected array $has = [];

	/**
	 * Relationships to load entities with.
	 *
	 * @var callable[]|string[]
	 */
	protected array $with = [];

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

	/**
	 * Set the relationships to check existence for.
	 *
	 * @param callable[]|string[]|string $relationships
	 * @return $this
	 */
	public function has($relationships): Query
	{
		$this->has = (array) $relationships;

		return $this;
	}

	/**
	 * Set the relationships to load entities with.
	 *
	 * @param callable[]|string[]|string $relationships
	 * @return $this
	 */
	public function with($relationships): Query
	{
		$this->with = (array) $relationships;

		return $this;
	}

	/**
	 * Run the query.
	 *
	 * @return object[]
	 */
	public function run()
	{
		return $this->mapper->run($this);
	}
}
