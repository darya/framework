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
		$mapper = $this->graph->getMapper($query->entity);
		$storage = $mapper->getStorage();
		$storageKey = $mapper->getEntityMap()->getStorageKey();

		$idQuery = clone $query;
		$idQuery->fields([$storageKey]);
		$ids = $storage->run($idQuery);

		// TODO: Check related entity existence ($query->has) to filter down IDs

		// TODO: Load root entities by ID

		// TODO: Load related entities and map to the root entities ($query->with)

		//return $entities;
	}
}
