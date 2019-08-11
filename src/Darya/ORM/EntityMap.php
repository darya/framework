<?php

namespace Darya\ORM;

/**
 * Darya's abstract entity map.
 *
 * Describes an entity's mapping to a storage resource.
 *
 * TODO: EntityMapFactory for easy (read: dynamic) instantiation with sensible defaults
 *
 * TODO: Could an entity factory go here too?
 *       This would give the entity map control over how entities are instantiated.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class EntityMap
{
	/**
	 * The class name of the entity to map.
	 *
	 * @var string
	 */
	protected $class;

	/**
	 * The name of the resource the entity maps to in storage.
	 *
	 * @var string
	 */
	protected $resource;

	/**
	 * The mapping of entity properties to storage fields.
	 *
	 * @var array
	 */
	protected $mapping = [];

	/**
	 * The mapping strategy to use.
	 *
	 * @var Strategy
	 */
	protected $strategy;

	/**
	 * The entity's primary key attribute.
	 *
	 * TODO: Composite keys
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Create a new entity map.
	 *
	 * @param string   $class    The class name of the entity to map.
	 * @param string   $resource The name of the resource the entity maps to in storage.
	 * @param array    $mapping  The mapping of entity attributes to storage fields.
	 * @param Strategy $strategy The mapping strategy to use.
	 * @param string   $key      [optional] The entity's primary key attribute. Defaults to `'id'`.
	 */
	public function __construct(string $class, string $resource, array $mapping, Strategy $strategy, string $key = 'id')
	{
		$this->class    = $class;
		$this->resource = $resource;
		$this->mapping  = $mapping;
		$this->strategy = $strategy;
		$this->key      = $key ?? $this->key;
	}

	/**
	 * Get the mapped entity class name.
	 *
	 * @return string
	 */
	public function getClass(): string
	{
		return $this->class;
	}

	/**
	 * Get the resource the entity is mapped to.
	 *
	 * @return string
	 */
	public function getResource(): string
	{
		return $this->resource;
	}

	/**
	 * Get the mapping of entity attributes to storage fields.
	 *
	 * Returns an array with entity attributes for keys and corresponding
	 * storage fields for values.
	 *
	 * @return string[]
	 */
	public function getMapping(): array
	{
		return $this->mapping;
	}

	/**
	 * Get the mapping strategy.
	 *
	 * @return Strategy
	 */
	public function getStrategy(): Strategy
	{
		return $this->strategy;
	}

	/**
	 * Get the entity's primary key attribute name.
	 *
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * Get the storage field name of the entity's primary key.
	 *
	 * @return string
	 */
	public function getStorageKey(): string
	{
		return $this->getStorageField($this->getKey());
	}

	/**
	 * Get the storage field name of the given entity property.
	 *
	 * @param string $property
	 * @return string
	 */
	public function getStorageField(string $property): string
	{
		if (array_key_exists($property, $this->mapping)) {
			return $this->mapping[$property];
		}

		return $property;
	}
}
