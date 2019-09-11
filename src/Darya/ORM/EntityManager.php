<?php

namespace Darya\ORM;

use Darya\Storage\Queryable;

/**
 * Darya's entity manager.
 *
 * Uses an entity graph and a set of entity mappers retrieve and persist entities.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class EntityManager
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
	 * @var Queryable[]
	 */
	protected $storages;

	/**
	 * The default storage.
	 *
	 * @var Queryable
	 */
	protected $defaultStorage;

	/**
	 * Create a new entity manager.
	 *
	 * @param EntityGraph $graph    The entity graph.
	 * @param Queryable[] $storages Storages keyed by name.
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
	 * TODO: Memoize
	 *
	 * @param string $entityName The entity name.
	 * @return Mapper
	 */
	public function mapper(string $entityName): Mapper
	{
		return new Mapper($this->graph->getEntityMap($entityName), $this->defaultStorage);
	}

	/**
	 * Find an entity with the given ID.
	 *
	 * @param string $entity
	 * @param mixed  $id
	 * @return object|null
	 */
	public function find(string $entity, $id)
	{
		return $this->mapper($entity)->find($id);
	}

	/**
	 * Find many entities with the given IDs.
	 *
	 * @param string  $entity
	 * @param mixed[] $id
	 * @return object|null
	 */
	public function findMany(string $entity, array $id)
	{
		return $this->mapper($entity)->findMany($id);
	}

	/**
	 * Open an ORM query builder.
	 *
	 * @param string $entity
	 * @return Query\Builder
	 */
	public function query(string $entity)
	{
		$builder = $this->mapper($entity)->query();
		$query   = new Query($builder->query, $entity);

		return new Query\Builder($query, $this);
	}

	/**
	 * Run an ORM query.
	 *
	 * TODO: Perhaps the relationship loading complexities here should be handled in the mapper.
	 *       This method feels like it should remain simple.
	 *
	 * @param Query $query
	 * @return object[]
	 */
	public function run(Query $query)
	{
		// Load root entity IDs
		$mapper     = $this->mapper($query->entity);
		$storage    = $mapper->getStorage();
		$storageKey = $mapper->getEntityMap()->getStorageKey();

		$fields = $query->fields;
		$query->fields($storageKey);
		$idEntities = $storage->run($query->storageQuery)->data;

		// TODO: Cleaner ID pluck with a helper function perhaps
		$ids = [];

		foreach ($idEntities as $idEntity) {
			$ids[] = $idEntity[$storageKey];
		}

		// TODO: Check related entity existence ($query->has) to filter down IDs

		// Load root entities by ID
		$query->fields($fields)->where($storageKey, $ids);
		$entitiesResult = $storage->run($query->storageQuery);

		$entities = $mapper->newInstancesFromStorage($entitiesResult->data);

		// TODO: Load related entities and map them to the root entities ($query->with)

		return $entities;
	}
}
