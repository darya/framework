<?php

namespace Darya\ORM;

use Darya\Storage\Query;
use Darya\Storage\Queryable;
use Darya\Storage\Result;
use ReflectionClass;
use ReflectionException;

/**
 * Darya's entity mapper.
 *
 * Maps a single type of entity to a queryable storage interface.
 *
 * TODO: Entity factory for instantiation; this could allow dynamically defined entities
 * TODO: Entity caching
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Mapper
{
	/**
	 * The EntityMap to map to storage.
	 *
	 * @var EntityMap
	 */
	protected $entityMap;

	/**
	 * The storage to map to.
	 *
	 * @var Queryable
	 */
	protected $storage;

	/**
	 * Create a new mapper.
	 *
	 * @param EntityMap $entityMap The entity map to use.
	 * @param Queryable $storage   The storage to map to.
	 */
	public function __construct(EntityMap $entityMap, Queryable $storage)
	{
		$this->entityMap = $entityMap;
		$this->storage   = $storage;
	}

	/**
	 * Create a new instance of the mapper's entity.
	 *
	 * TODO: Use a hydration library, or have EntityMap decide how to hydrate.
	 *       Entities themselves shouldn't need to implement an interface.
	 *
	 * @param array $entityData The data to set on the created entity instance.
	 * @return Mappable
	 * @throws ReflectionException
	 */
	public function newInstance(array $entityData = []): Mappable
	{
		$reflection = new ReflectionClass($this->entityMap->getClass());

		/**
		 * @var Mappable $instance
		 */
		$instance = $reflection->newInstance();
		$instance->setAttributeData($entityData);

		return $instance;
	}

	/**
	 * Find a single entity with the given ID.
	 *
	 * @param mixed $id The ID of the entity to find.
	 * @return Mappable
	 */
	public function find($id): ?Mappable
	{
		$entities = $this->query()
			->where($this->entityMap->getStorageKey(), $id)
			->run();

		if (!count($entities)) {
			return null;
		}

		return $entities[0];
	}

	/**
	 * Find all entities.
	 *
	 * @return Mappable[]
	 */
	public function all(): array
	{
		return $this->query()->run();
	}

	/**
	 * Open a query to the storage that the entity is mapped to.
	 *
	 * @return Query\Builder
	 */
	public function query(): Query\Builder
	{
		$query = $this->storage->query($this->entityMap->getResource());

		$query->callback(function (Result $result) {
			$entities = [];

			foreach ($result as $storageData) {
				$entityData = $this->mapToEntityData($storageData);
				$entities[] = $this->newInstance($entityData);
			}

			return $entities;
		});

		return $query;
	}

	/**
	 * Store an entity to its mapped storage.
	 *
	 * Creates or updates the entity in storage depending on whether it exists.
	 *
	 * If storage returns a key after a create query, it will be set on the entity.
	 *
	 * @param Mappable $entity
	 * @return Mappable The mapped entity
	 */
	public function store(Mappable $entity): Mappable
	{
		$resource    = $this->entityMap->getResource();
		$storageKey  = $this->entityMap->getStorageKey();
		$storageData = $this->mapToStorageData($entity->getAttributeData());

		// Determine whether the entity exists in storage
		$id     = $storageData[$storageKey] ?? null;
		$exists = false;

		if ($id !== null) {
			$query  = $this->storage->query($resource);
			$result = $query->where($storageKey, $id)->run();
			$exists = $result->count > 0;
		}

		// Update or create in storage accordingly
		$query = $this->storage->query($resource);

		if ($exists) {
			$query->update($storageData)->where($storageKey, $id)->run();

			return $entity;
		}

		$result = $query->create($storageData)->run();

		// Set the insert ID as the entity's key, if one is returned
		if ($result->insertId) {
			$key = $this->entityMap->getKey();

			$attributes       = $entity->getAttributeData();
			$attributes[$key] = $result->insertId;
			$entity->setAttributeData($attributes);
		}

		return $entity;
	}

	/**
	 * Map the given storage data to entity data.
	 *
	 * TODO: Extract to strategy interface, and actually mutate an entity (rename to mapToEntity($entity, $data))
	 *
	 * @param array $storageData The storage data to map to entity data
	 * @return array The resulting entity data
	 */
	protected function mapToEntityData(array $storageData): array
	{
		$entityData = [];
		$mapping    = $this->entityMap->getMapping();

		foreach ($mapping as $entityKey => $storageKey) {
			if (array_key_exists($storageKey, $storageData)) {
				$entityData[$entityKey] = $storageData[$storageKey];
			}
		}

		return $entityData;
	}

	/**
	 * Map the given storage data to storage data.
	 *
	 * TODO: Extract to strategy interface, and actually read from an entity (rename to readFromEntity($entity, $data))
	 *
	 * @param array $entityData The entity data to map to storage data
	 * @return array The resulting storage data
	 */
	protected function mapToStorageData(array $entityData): array
	{
		$storageData = [];
		$mapping     = $this->entityMap->getMapping();

		foreach ($mapping as $entityKey => $storageKey) {
			if (array_key_exists($entityKey, $entityData)) {
				$storageData[$entityKey] = $entityData[$storageKey];
			}
		}

		return $storageData;
	}
}
