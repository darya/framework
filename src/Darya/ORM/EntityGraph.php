<?php

namespace Darya\ORM;

/**
 * Darya's entity graph.
 *
 * Maintains relationships between different entity types.
 *
 * TODO: Use an actual graph implementation with Nodes containing entity name, maps and relationships?
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class EntityGraph
{
	/**
	 * Entity names.
	 *
	 * @var string[]
	 */
	protected $entities = [];

	/**
	 * Entity maps.
	 *
	 * Keyed by entity name.
	 *
	 * @var EntityMap[]
	 */
	protected $maps = [];

	/**
	 * Entity relationships.
	 *
	 * Keyed by entity name.
	 *
	 * @var Relation[]
	 */
	protected $relationships = [];

	/**
	 * Create a new entity graph.
	 *
	 * @param EntityMap[] $entityMaps    Entity maps.
	 * @param Relation[]  $relationships Entity relationships.
	 */
	public function __construct(array $entityMaps = [], array $relationships = [])
	{
		$this->addMaps($entityMaps);
		$this->addRelationships($relationships);
	}

	/**
	 * Add a new entity to the graph.
	 *
	 * @param string $name Entity name.
	 */
	protected function addEntity(string $name)
	{
		if (!in_array($name, $this->entities)) {
			$this->entities[] = $name;
		}

		if (!isset($this->maps[$name])) {
			$this->maps[$name] = [];
		}

		if (!isset($this->relationships[$name])) {
			$this->relationships[$name] = [];
		}
	}

	/**
	 * @param string[] $entities
	 */
	public function addEntities(array $entities)
	{
		foreach ($entities as $name) {
			$this->addEntity($name);
		}
	}

	/**
	 * @param EntityMap $map
	 */
	public function addMap(EntityMap $map)
	{
		$entityName = $map->getName();

		$this->addEntity($entityName);

		$this->maps[$entityName][] = $map;
	}

	/**
	 * @param EntityMap[] $maps
	 */
	public function addMaps(array $maps)
	{
		foreach ($maps as $map) {
			$this->addMap($map);
		}
	}

	/**
	 * @param Relation $relationship
	 */
	public function addRelationship(Relation $relationship)
	{
		// TODO: Class name should be explicitly provided by the relationship class
		$entityName = $relationship->name() ?: get_class($relationship->parent);

		$this->addEntity($entityName);

		$this->relationships[$entityName][] = $relationship;
	}

	/**
	 * @param Relation[] $relationships
	 */
	public function addRelationships(array $relationships)
	{
		foreach ($relationships as $relationship) {
			$this->addRelationship($relationship);
		}
	}
}
