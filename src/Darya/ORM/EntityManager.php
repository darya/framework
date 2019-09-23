<?php

namespace Darya\ORM;

use Darya\ORM\Exception\EntityNotFoundException;
use Darya\Storage;

/**
 * Darya's entity manager.
 *
 * Uses an entity graph and a set of entity mappers retrieve and persist entities.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class EntityManager implements Storage\Queryable
{
	/**
	 * The entity graph.
	 *
	 * @var EntityGraph
	 */
	protected $graph;

	/**
	 * Storages keyed by name.
	 *
	 * @var Storage\Queryable[]
	 */
	protected $storages;

	/**
	 * The default storage.
	 *
	 * @var Storage\Queryable
	 */
	protected $defaultStorage;

	/**
	 * Create a new entity manager.
	 *
	 * @param EntityGraph         $graph    The entity graph.
	 * @param Storage\Queryable[] $storages Storages keyed by name.
	 */
	public function __construct(EntityGraph $graph, array $storages)
	{
		$this->graph    = $graph;
		$this->storages = $storages;

		// TODO: $this->validateStorages($storages);
		// TODO: $this->addStorages($storages)
		if (!empty($storages)) {
			$this->defaultStorage = $storages[0];
		}
	}

	/**
	 * Get a mapper for a given entity.
	 *
	 * TODO: Memoize Mappers
	 *
	 * @param string      $entity  The entity name.
	 * @param string|null $storage The storage to use.
	 * @return Mapper
	 */
	public function mapper(string $entity, string $storage = null): Mapper
	{
		if ($storage !== null) {
			if (!isset($this->storages[$storage])) {
				// TODO: MappingException
				throw new \InvalidArgumentException("Unknown storage '$storage'");
			}

			$storage = $this->storages[$storage];
		}

		return new Mapper(
			$this,
			$this->graph->getEntityMap($entity),
			$storage ?? $this->defaultStorage
		);
	}

	/**
	 * Find an entity with the given ID.
	 *
	 * @param string $entity The entity to find.
	 * @param mixed  $id     The ID of the entity to find.
	 * @return object|null
	 */
	public function find(string $entity, $id)
	{
		return $this->mapper($entity)->find($id);
	}

	/**
	 * Find a single entity with the given ID or create a new one if it not found.
	 *
	 * @param string $entity The entity to find.
	 * @param mixed  $id     The ID of the entity to find.
	 * @return object The entity.
	 */
	public function findOrNew(string $entity, $id)
	{
		return $this->mapper($entity)->findOrNew($id);
	}

	/**
	 * /**
	 * Find a single entity with the given ID or error if it is not found.
	 *
	 * Throws an EntityNotFoundException if the entity is not found.
	 *
	 * @param string $entity
	 * @param mixed  $id
	 * @return object
	 * @throws EntityNotFoundException
	 */
	public function findOrFail(string $entity, $id)
	{
		return $this->mapper($entity)->findOrFail($id);
	}

	/**
	 * Find many entities with the given IDs.
	 *
	 * @param string  $entity The entity to find.
	 * @param mixed[] $id     The ID of the entity to find.
	 * @return object|null The entities.
	 */
	public function findMany(string $entity, array $id)
	{
		return $this->mapper($entity)->findMany($id);
	}

	/**
	 * Find all entities.
	 *
	 * @param string $entity The entity to find.
	 * @return object[] The entities.
	 */
	public function all(string $entity)
	{
		return $this->mapper($entity)->all();
	}

	/**
	 * Open an ORM query builder.
	 *
	 * @param string $entity The entity to query.
	 * @param array  $fields The entity fields to retrieve.
	 * @return Query\Builder
	 */
	public function query($entity, $fields = []): Query\Builder
	{
		return $this->mapper($entity)->query();
	}

	/**
	 * Run a query.
	 *
	 * TODO: Perhaps the relationship loading complexities here should be handled in the Mapper.
	 *       This method feels like it should remain simple.
	 *       The Mapper could retrieve related Mappers from an EntityManager instance.
	 *
	 * @param Storage\Query $query
	 * @return object[]
	 */
	public function run(Storage\Query $query)
	{
		$query = $this->prepareQuery($query);

		// Load root entity IDs
		$mapper     = $query->mapper;
		$storage    = $mapper->getStorage();
		$storageKey = $mapper->getEntityMap()->getStorageKey();

		$fields = $query->fields;
		$query->fields($storageKey);
		$ids = array_column($storage->run($query)->data, $storageKey);

		// TODO: Check related entity existence ($query->has) to filter down IDs
		//       OR use a subquery in the below query (when count() is a thing)

		// Load root entities by ID
		$query->fields($fields)->where($storageKey, $ids);
		$entitiesResult = $storage->run($query);

		$entities = $mapper->newInstances($entitiesResult->data);

		// TODO: Load related entities and map them to the root entities ($query->with)

		return $entities;
	}

	/**
	 * Prepare a query as an ORM query.
	 *
	 * @param Storage\Query $storageQuery The query to prepare.
	 * @return Query The prepared query.
	 */
	protected function prepareQuery(Storage\Query $storageQuery): Query
	{
		if ($storageQuery instanceof Query) {
			return $storageQuery;
		}

		$mapper = $this->mapper($storageQuery->resource);

		$ormQuery = new Query($mapper);
		$ormQuery->copyFrom($storageQuery);
		$ormQuery->resource($mapper->getEntityMap()->getResource());

		return $ormQuery;
	}
}
