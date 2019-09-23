<?php

namespace Darya\ORM;

use Darya\ORM\Exception\EntityNotFoundException;
use Darya\Storage;
use ReflectionClass;
use ReflectionException;

/**
 * Darya's entity mapper.
 *
 * Maps a single type of entity to a queryable storage interface.
 *
 * TODO: Entity factory for instantiation; this could allow dynamically defined entities
 * TODO: Entity caching
 * TODO: Map to array, for lighter data mapping that avoids any instantiation
 * TODO: Consider other types of storage, e.g. a cache, which may only find by ID
 * TODO: Composite key support
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Mapper
{
	/**
	 * @var EntityManager
	 */
	private $orm;

	/**
	 * The EntityMap to map to storage.
	 *
	 * @var EntityMap
	 */
	protected $entityMap;

	/**
	 * The storage to map to.
	 *
	 * @var Storage\Queryable
	 */
	protected $storage;

	/**
	 * Create a new mapper.
	 *
	 * @param EntityManager     $orm
	 * @param EntityMap         $entityMap The entity map to use.
	 * @param Storage\Queryable $storage   The storage to map to.
	 */
	public function __construct(EntityManager $orm, EntityMap $entityMap, Storage\Queryable $storage)
	{
		$this->orm       = $orm;
		$this->entityMap = $entityMap;
		$this->storage   = $storage;
	}

	/**
	 * Check whether a single entity exists with the given ID.
	 *
	 * @param mixed $id The ID of the entity to check.
	 * @return bool
	 */
	public function has($id)
	{
		if ($id === null) {
			return false;
		}

		$entityMap  = $this->getEntityMap();
		$resource   = $entityMap->getResource();
		$storageKey = $entityMap->getStorageKey();

		$result = $this->storage->query($resource)
			->fields([$storageKey])
			->where($storageKey, $id)
			->limit(1)
			->run();

		$exists = $result->count > 0;

		return $exists;
	}

	/**
	 * Find a single entity with the given ID.
	 *
	 * Returns null if the entity is not found.
	 *
	 * @param mixed $id The ID of the entity to find.
	 * @return object|null The entity, or null if it is not found.
	 */
	public function find($id)
	{
		$storageKey = $this->getEntityMap()->getStorageKey();

		$entities = $this->query()
			->where($storageKey, $id)
			->run();

		if (!count($entities)) {
			return null;
		}

		return $entities[0];
	}

	/**
	 * Find a single entity with the given ID or error if it is not found.
	 *
	 * Throws an EntityNotFoundException if the entity is not found.
	 *
	 * @param mixed $id The ID of the entity to find.
	 * @return object The entity.
	 * @throws EntityNotFoundException When the entity is not found.
	 */
	public function findOrFail($id)
	{
		$entity = $this->find($id);

		if ($entity !== null) {
			return $entity;
		}

		$name = $this->getEntityMap()->getName();

		throw (new EntityNotFoundException())->setEntityName($name);
	}

	/**
	 * Find a single entity with the given ID or create a new one if it not found.
	 *
	 * @param mixed $id The ID of the entity to find.
	 * @return object The entity.
	 */
	public function findOrNew($id)
	{
		return $this->find($id) ?: $this->newInstance();
	}

	/**
	 * Find the entities with the given IDs.
	 *
	 * @param mixed[] $ids The IDs of the entities to find.
	 * @return object[]
	 */
	public function findMany(array $ids): array
	{
		$storageKey = $this->getEntityMap()->getStorageKey();

		return $this->query()
			->where($storageKey, $ids)
			->run();
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
		$query = new Query\Builder(
			new Query($this),
			$this->getStorage()
		);

		$query->callback(function (Storage\Result $result) {
			return $this->newInstances($result->data);
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
		$entityMap   = $this->getEntityMap();
		$resource    = $entityMap->getResource();
		$storageKey  = $entityMap->getStorageKey();
		$storageData = $this->mapToStorage($entity);

		// Determine whether the entity exists in storage
		$id     = $storageData[$storageKey] ?? null;
		$exists = $this->has($id);

		// Update or create in storage accordingly
		$query = $this->storage->query($resource);

		if ($exists) {
			$query->update($storageData)
				->where($storageKey, $id)
				->run();

			return $entity;
		}

		$result = $query->create($storageData)->run();

		// Set the insert ID as the entity's key, if one is returned
		if ($result->insertId) {
			$storageData              = $this->mapToStorage($entity);
			$storageData[$storageKey] = $result->insertId;
			$entity                   = $this->mapFromStorage($entity, $storageData);
		}

		return $entity;
	}

	/**
	 * Delete an entity from its mapped storage.
	 *
	 * @param object $entity
	 */
	public function delete($entity)
	{
		$entityMap   = $this->getEntityMap();
		$resource    = $entityMap->getResource();
		$storageKey  = $entityMap->getStorageKey();
		$storageData = $this->mapToStorage($entity);

		$this->storage->query($resource)
			->where($storageKey, $storageData[$storageKey])
			->delete();
	}

	/**
	 * Create a new instance of the mapper's entity.
	 *
	 * TODO: Factory work should happen here, via the entity map or otherwise
	 *
	 * @return object
	 */
	public function newInstance()
	{
		$reflection = new ReflectionClass($this->getEntityMap()->getClass());

		return $reflection->newInstance();
	}

	/**
	 * Create new instances of the mapper's entity from the given storage data.
	 *
	 * @param array $storageData The storage data to create entities from.
	 * @return array The new entities.
	 */
	public function newInstances(array $storageData)
	{
		$entities = [];

		foreach ($storageData as $entityDatum) {
			$entities[] = $this->mapFromStorage($this->newInstance(), $entityDatum);
		}

		return $entities;
	}

	/**
	 * Get the entity map.
	 *
	 * @return EntityMap
	 */
	public function getEntityMap(): EntityMap
	{
		return $this->entityMap;
	}

	/**
	 * Get the storage to map to.
	 *
	 * @return Storage\Queryable
	 */
	public function getStorage(): Storage\Queryable
	{
		return $this->storage;
	}

	/**
	 * Map from storage data to an entity.
	 *
	 * @param object $entity      The entity to map to.
	 * @param array  $storageData The storage data to map from.
	 * @return object The resulting entity.
	 */
	public function mapFromStorage($entity, array $storageData)
	{
		$entityMap = $this->getEntityMap();
		$mapping   = $entityMap->getMapping();

		return $entityMap->getStrategy()->mapFromStorage($entity, $mapping, $storageData);
	}

	/**
	 * Map from an entity to storage data.
	 *
	 * @param object $entity The entity to map from.
	 * @return array The resulting storage data.
	 */
	public function mapToStorage($entity): array
	{
		$entityMap = $this->getEntityMap();
		$mapping   = $entityMap->getMapping();

		return $entityMap->getStrategy()->mapToStorage($entity, $mapping);
	}

	/**
	 * Map an ORM query to a storage query.
	 *
	 * This method does not map relationship loading in any way.
	 *
	 * @param Query $query The ORM query to map.
	 * @return Storage\Query The mapped storage query.
	 */
	protected function mapToStorageQuery(Query $query): Storage\Query
	{
		$entityMap = $this->getEntityMap();
		$resource  = $entityMap->getResource();

		$storageQuery = new Storage\Query($resource);

		// TODO: Map all other identifiers in the query; fields, filters, etc

		$storageQuery->copyFrom($query);
		$storageQuery->resource($resource);

		return $storageQuery;
	}
}
