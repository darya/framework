<?php

namespace Darya\ORM;

use Darya\ORM\EntityMap\Strategy;
use Darya\Storage;
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
 * TODO: Storage name could be kept here too, for the EntityManager to use to create Mappers
 *       Work out the best way to handle this relationship between mappings and storages though
 *       A single mapping could be used for multiple storage backends
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class EntityMap
{
	/**
	 * The name of the entity.
	 *
	 * @var string|null
	 */
	protected ?string $name;

	/**
	 * The class name of the entity to map.
	 *
	 * @var string
	 */
	protected string $class;

	/**
	 * The storage the entity maps to.
	 *
	 * @var Storage\Queryable
	 */
	protected Storage\Queryable $storage;

	/**
	 * The name of the resource the entity maps to in storage.
	 *
	 * @var string
	 */
	protected string $resource;

	/**
	 * The key-value mapping of entity attributes to storage fields.
	 *
	 * @var array<string, string>
	 */
	protected array $mapping = [];

	/**
	 * The mapping strategy to use.
	 *
	 * @var Strategy
	 */
	protected Strategy $strategy;

	/**
	 * The entity's primary key attribute(s).
	 *
	 * @var string|string[]
	 */
	protected $key;

	/**
	 * Create a new entity map.
	 *
	 * @param string            $class    The class name of the entity to map.
	 * @param string            $resource The name of the resource the entity maps to in storage.
	 * @param array             $mapping  The key-value mapping of entity attributes to storage fields.
	 * @param Strategy          $strategy The mapping strategy to use.
	 * @param string|string[]   $key      [optional] The entity's primary key attribute(s). Defaults to `'id'`.
	 */
	public function __construct(string $class, string $resource, array $mapping, Strategy $strategy, $key = 'id')
	{
		$this->class    = $class;
		$this->resource = $resource;
		$this->mapping  = $mapping;
		$this->strategy = $strategy;
		$this->setKey($key);
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
	public function setName(string $name): void
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
	 * Map from storage data to an entity.
	 *
	 * @param object $entity      The entity to map to.
	 * @param array  $storageData The storage data to map from.
	 * @return object The mapped entity.
	 */
	public function mapFromStorage(object $entity, array $storageData): object
	{
		$mapping = $this->getMapping();

		foreach ($mapping as $entityKey => $storageKey) {
			if (array_key_exists($storageKey, $storageData)) {
				$this->writeAttribute($entity, $entityKey, $storageData[$storageKey]);
			}
		}

		return $entity;
	}

	/**
	 * Map from an entity to storage data.
	 *
	 * @param object $entity The entity to map from.
	 * @return array The mapped storage data.
	 */
	public function mapToStorage(object $entity): array
	{
		$mapping = $this->getMapping();

		$data = [];

		foreach ($mapping as $entityKey => $storageKey) {
			$data[$storageKey] = $this->readAttribute($entity, $entityKey);
		}

		return $data;
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
	protected function setKey($key): void
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

	/**
	 * Read an attribute from an entity.
	 *
	 * @param mixed  $entity    The entity to read the attribute from.
	 * @param string $attribute The name of the attribute to read.
	 * @return mixed The attribute's value.
	 */
	public function readAttribute($entity, string $attribute)
	{
		return $this->readAttributes($entity, [$attribute])[$attribute];
	}

	/**
	 * Read many attributes from an entity.
	 *
	 * @param mixed    $entity     The entity to read the attributes from.
	 * @param string[] $attributes The names of the attributes to read.
	 * @return mixed[] The attribute values.
	 */
	public function readAttributes($entity, array $attributes): array
	{
		return $this->getStrategy()->readAttributes($entity, $attributes);
	}

	/**
	 * Read an attribute from many entities.
	 *
	 * @param iterable $entities  The entities to read the attribute from.
	 * @param string   $attribute The name of the attribute to read.
	 * @return mixed[] The attribute values from each entity.
	 */
	public function readAttributeFromMany(iterable $entities, string $attribute): array
	{
		$values = [];

		foreach ($entities as $entity) {
			$values[] = $this->readAttribute($entity, $attribute);
		}

		return $values;
	}

	/**
	 * Write an attribute to an entity.
	 *
	 * @param object $entity    The entity to write the attribute to.
	 * @param string $attribute The name of the attribute to write.
	 * @param mixed  $value     The value of the attribute to write.
	 * @return void
	 */
	public function writeAttribute($entity, string $attribute, $value): void
	{
		$this->writeAttributes($entity, [$attribute => $value]);
	}

	/**
	 * Write many attributes to an entity.
	 *
	 * @param object  $entity     The entity to write the attributes to.
	 * @param mixed[] $attributes The names and values of the attributes to write.
	 * @return void
	 */
	public function writeAttributes($entity, array $attributes): void
	{
		$this->getStrategy()->writeAttributes($entity, $attributes);
	}
}
