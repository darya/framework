<?php
namespace Darya\ORM;

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
	 * @var EntityGraph
	 */
	protected $graph;

	/**
	 * Create a new entity manager.
	 *
	 * @param EntityGraph $graph
	 */
	public function __construct(EntityGraph $graph)
	{
		$this->graph = $graph;
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
		return $this->graph->getMapper($entity)->find($id);
	}

	/**
	 * Open an ORM query builder.
	 *
	 * @param string $entity
	 */
	public function query(string $entity)
	{
		$storageQuery = $this->graph->getMapper($entity)->query();
		$query = new Query($entity, $storageQuery);

		// TODO: return new ORM\Query\Builder($query, $this)
	}

	/**
	 * Run an ORM query.
	 *
	 * @param Query $query
	 * @return object[]
	 */
	public function run(Query $query)
	{
		// Load root entity IDs
		$rootMapper = $this->graph->getMapper($query->entity);
		$rootStorage = $rootMapper->getStorage();
		$rootEntityIds = $rootStorage->run($query->storageQuery->fields([$rootMapper->getEntityMap()->getStorageKey()]));

		// TODO: Check related entity existence ($query->has)

		// TODO: Load related entities and map to the root entities ($query->with)

		//return $rootEntities;
	}
}
