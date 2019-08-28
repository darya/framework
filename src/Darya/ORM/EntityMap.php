<?php

namespace Darya\ORM;

use InvalidArgumentException;

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
 * TODO: Should the storage interface be kept here? Perhaps at least its "name"?
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class EntityMap
{
	/**
	 * The name of the entity.
	 *
	 * @var string
	 */
	protected $name;

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
	 * The entity's primary key attribute(s).
	 *
	 * @var string|string[]
	 */
	protected $key;

	/**
	 * Create a new entity map.
	 *
	 * @param string          $class    The class name of the entity to map.
	 * @param string          $resource The name of the resource the entity maps to in storage.
	 * @param array           $mapping  The mapping of entity attributes to storage fields.
	 * @param Strategy        $strategy The mapping strategy to use.
	 * @param string|string[] $key      [optional] The entity's primary key attribute(s). Defaults to `'id'`.
	 */
	public function __construct(string $class, string $resource, array $mapping, Strategy $strategy, $key = 'id')
	{
		$this->class    = $class;
		$this->resource = $resource;
		$this->mapping  = $mapping;
		$this->strategy = $strategy;
		$this->key      = $key;
	}

	/**
	 * Get the name of the entity.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name ?: $this->getClass();
	}

	/**
	 * Set the name of the entity.
	 *
	 * @param string $name
	 */
	public function setName(string $name)
	{
		$this->name = $name;
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
	 * Get the entity's primary key attribute(s).
	 *
	 * @return string|string[]
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * Set the entity's primary key attribute(s).
	 *
	 * @param string|string[]
	 */
	protected function setKey($key)
	{
		if (!is_string($key) && !is_array($key)) {
			throw new InvalidArgumentException("Entity key must be a string, or an array of strings");
		}

		$this->key = $key;
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
	 * Get the storage field name of the given entity attribute.
	 *
	 * @param string $attribute
	 * @return string
	 */
	public function getStorageField(string $attribute): string
	{
		if (array_key_exists($attribute, $this->mapping)) {
			return $this->mapping[$attribute];
		}

		return $attribute;
	}

	/**
	 * Get the storage field names of the given entity attributes.
	 *
	 * @param string[] $attributes
	 * @return string[]
	 */
	public function getStorageFields(array $attributes): array
	{
		$fields = [];

		foreach ($attributes as $attribute) {
			$fields[] = $this->getStorageField($attribute);
		}

		return $fields;
	}

	/**
	 * Get the attribute name for the given storage field.
	 *
	 * @param string $storageField
	 * @return string
	 */
	public function getAttribute(string $storageField): string
	{
		return array_search($storageField, $this->getMapping()) ?: $storageField;
	}

	/**
	 * Get the attribute names for the given storage fields.
	 *
	 * @param string[] $storageFields
	 * @return string[]
	 */
	public function getAttributes(array $storageFields): array
	{
		$attributes = [];

		$flippedMapping = array_flip($this->getMapping());

		foreach ($storageFields as $storageField) {
			$attributes[] = $flippedMapping[$storageField] ?? $storageField;
		}

		return $attributes;
	}
}
