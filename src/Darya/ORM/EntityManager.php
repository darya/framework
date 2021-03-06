<?php

namespace Darya\ORM;

use Darya\ORM\Exception\EntityNotFoundException;
use Darya\ORM\Exception\MappingException;
use Darya\Storage;
use Darya\Storage\Queryable;
use RuntimeException;
use UnexpectedValueException;

/**
 * Darya's entity manager.
 *
 * Uses an entity graph and a set of storage interfaces retrieve and persist entities.
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
	protected EntityGraph $graph;

	/**
	 * Storages keyed by name.
	 *
	 * @var Storage\Queryable[]
	 */
	protected array $storages;

	/**
	 * The default storage name.
	 *
	 * @var string
	 */
	protected string $defaultStorageName;

	/**
	 * Cached entity mappers.
	 *
	 * Keyed by entity and storage.
	 *
	 * @var Mapper[]
	 */
	protected array $mappers = [];

	/**
	 * Create a new entity manager.
	 *
	 * @param EntityGraph         $graph    The entity graph.
	 * @param Storage\Queryable[] $storages Storages keyed by name.
	 */
	public function __construct(EntityGraph $graph, array $storages)
	{
		$this->graph = $graph;
		$this->addStorages($storages);
	}

	/**
	 * Add a storage.
	 *
	 * @param string    $name    The name of the storage.
	 * @param Queryable $storage The storage.
	 */
	public function addStorage(string $name, Queryable $storage)
	{
		if (isset($this->storages[$name])) {
			throw new RuntimeException("Storage '$name' is already set");
		}

		if (!isset($this->defaultStorageName)) {
			$this->defaultStorageName = $name;
		}

		$this->storages[$name] = $storage;
	}

	/**
	 * Add many storages.
	 *
	 * @param Queryable[] $storages Storages keyed by storage name.
	 * @throws RuntimeException If a storage is already set with a given name
	 * @throws UnexpectedValueException If a given storage is not an instance of Darya\Storage\Queryable
	 */
	public function addStorages(array $storages)
	{
		foreach ($storages as $name => $storage) {
			$this->addStorage($name, $storage);
		}
	}

	/**
	 * Get the default storage.
	 *
	 * @return Queryable
	 * @throws RuntimeException If no default storage is set
	 */
	public function getDefaultStorage(): Queryable
	{
		$storage = $this->storages[$this->defaultStorageName] ?? null;

		if ($storage === null) {
			throw new RuntimeException("No default storage available");
		}

		return $storage;
	}

	/**
	 * Get the entity graph.
	 *
	 * @return EntityGraph
	 */
	public function graph(): EntityGraph
	{
		return $this->graph;
	}

	/**
	 * Get a mapper for a given entity.
	 *
	 * TODO: Memoize Mappers
	 *
	 * @param string      $entity  The entity name.
	 * @param string|null $storage The storage to use.
	 * @return Mapper The entity's mapper.
	 */
	public function mapper(string $entity, string $storage = null): Mapper
	{
		if ($storage === null) {
			$storage = $this->defaultStorageName;
		}

		if (!isset($this->storages[$storage])) {
			throw MappingException::unknownStorage($storage);
		}

		$storage   = $this->storages[$storage];
		$entityMap = $this->graph->getEntityMap($entity);

		return new Mapper($this, $entityMap, $storage);
	}

	/**
	 * Find an entity with the given ID.
	 *
	 * @param string $entity The entity to find.
	 * @param mixed  $id     The ID of the entity to find.
	 * @return object|null The entity, if found. `null` otherwise.
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
	 * @return object The entity, found or created.
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
	 * @return object[] The entities found.
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
		return $this->mapper($entity)->query()->fields($fields);
	}

	/**
	 * Run a query.
	 *
	 * @param Storage\Query $query
	 * @return object[]
	 */
	public function run(Storage\Query $query)
	{
		return $this->prepareQuery($query)->run();
	}

	/**
	 * Prepare a storage query as an ORM query.
	 *
	 * Returns the query unmodified if it is already an ORM query.
	 *
	 * @param Storage\Query $storageQuery The query to prepare.
	 * @return Query The prepared query.
	 */
	public function prepareQuery(Storage\Query $storageQuery): Query
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
