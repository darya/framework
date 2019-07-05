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
	 * TODO: Factory work should happen here
	 *
	 * @return object
	 * @throws ReflectionException
	 */
	public function newInstance()
	{
		$reflection = new ReflectionClass($this->entityMap->getClass());

		return $reflection->newInstance();
	}

	/**
	 * Find a single entity with the given ID.
	 *
	 * @param mixed $id The ID of the entity to find.
	 * @return object
	 */
	public function find($id)
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
	 * @return object[]
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
				$entity = $this->mapToEntity($this->newInstance(), $storageData);

				$entities[] = $entity;
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
	 * @param object $entity
	 * @return object The mapped entity
	 */
	public function store($entity)
	{
		$resource    = $this->entityMap->getResource();
		$storageKey  = $this->entityMap->getStorageKey();
		$storageData = $this->mapToStorageData($entity);

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
	 * Map storage data to the given entity.
	 *
	 * @param object $entity The entity to map to.
	 * @param array $storageData The storage data to map from.
	 * @return object The resulting entity.
	 */
	protected function mapToEntity($entity, array $storageData)
	{
		return $this->entityMap->getStrategy()->mapToEntity($entity, $storageData);
	}

	/**
	 * Map the given storage data to storage data.
	 *
	 * @param object $entity The entity to map from.
	 * @return array The resulting storage data.
	 */
	protected function mapToStorageData($entity): array
	{
		return $this->entityMap->getStrategy()->mapToStorage($entity);
	}
}
