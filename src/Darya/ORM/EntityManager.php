<?php

namespace Darya\ORM;

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
	 * TODO: Memoize
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
	 * @param string $entity The entity to query.
	 * @param array  $fields The entity fields to retrieve.
	 * @return Query\Builder
	 */
	public function query($entity, $fields = []): Query\Builder
	{
		$mapper  = $this->mapper($entity);
		$builder = $mapper->query();
		$query   = new Query($entity, $builder->query->resource);

		return new Query\Builder($query, $this);
	}

	/**
	 * Run a query.
	 *
	 * @param Storage\Query $query
	 * @return object[]
	 */
	public function run(Storage\Query $query)
	{
		if ($query instanceof Query) {
			return $this->runOrmQuery($query);
		}

		$mapper = $this->mapper($query->resource);

		$query->resource($mapper->getEntityMap()->getResource());

		$result = $mapper->getStorage()->run($query);

		$entities = $mapper->newInstances($result->data);

		return $entities;
	}

	/**
	 * Run an ORM query.
	 *
	 * TODO: Perhaps the relationship loading complexities here should be handled in the Mapper.
	 *       This method feels like it should remain simple.
	 *       The Mapper could retrieve related Mappers from an EntityManager instance.
	 *
	 * @param Query $query
	 * @return array
	 */
	protected function runOrmQuery(Query $query)
	{
		// Load root entity IDs
		$mapper     = $this->mapper($query->entity);
		$storage    = $mapper->getStorage();
		$storageKey = $mapper->getEntityMap()->getStorageKey();

		$query->resource($mapper->getEntityMap()->getResource());

		$fields = $query->fields;
		$query->fields($storageKey);
		$idEntities = $storage->run($query)->data;

		// TODO: Cleaner ID pluck with a helper function perhaps
		$ids = [];

		foreach ($idEntities as $idEntity) {
			$ids[] = $idEntity[$storageKey];
		}

		// TODO: Check related entity existence ($query->has) to filter down IDs

		// Load root entities by ID
		$query->fields($fields)->where($storageKey, $ids);
		$entitiesResult = $storage->run($query);

		$entities = $mapper->newInstances($entitiesResult->data);

		// TODO: Load related entities and map them to the root entities ($query->with)

		return $entities;
	}
}
