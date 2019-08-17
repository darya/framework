<?php

namespace Darya\ORM;

use RuntimeException;

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
	 * @var EntityMap[][]
	 */
	protected $maps = [];

	/**
	 * Entity relationships.
	 *
	 * Keyed by entity name.
	 *
	 * @var Relationship[][]
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
	 * Get a relationship of an entity.
	 *
	 * @param string $entityName       The entity name.
	 * @param string $relationshipName The relationship name.
	 * @return Relationship
	 * @throws RuntimeException
	 */
	public function getRelationship($entityName, $relationshipName): Relationship
	{
		if (!isset($this->entities[$entityName])) {
			throw new RuntimeException("Entity '$entityName' not found");
		}

		if (!isset($this->relationships[$entityName][$relationshipName])) {
			throw new RuntimeException("Relationship '$relationshipName' not found for entity '$entityName'");
		}

		return $this->relationships[$entityName][$relationshipName];
	}

	/**
	 * Get the relationships of an entity.
	 *
	 * @param string $entityName The entity name.
	 * @return Relationship[]
	 */
	public function getRelationships($entityName)
	{
		if (!isset($this->relationships[$entityName])) {
			throw new RuntimeException("Entity '$entityName' not found");
		}

		return $this->relationships[$entityName];
	}

	/**
	 * Add an entity map to the graph.
	 *
	 * @param EntityMap $map
	 */
	public function addMap(EntityMap $map)
	{
		$entityName = $map->getName();

		$this->addEntity($entityName);

		$this->maps[$entityName][] = $map;
	}

	/**
	 * Add many entity maps to the graph.
	 *
	 * @param EntityMap[] $maps
	 */
	public function addMaps(array $maps)
	{
		foreach ($maps as $map) {
			$this->addMap($map);
		}
	}

	/**
	 * Add a relationship to the graph.
	 *
	 * @param Relationship $relationship
	 */
	public function addRelationship(Relationship $relationship)
	{
		$entityName = $relationship->getParentMap()->getName();

		$this->addEntity($entityName);

		$this->relationships[$entityName][$relationship->getName()] = $relationship;
	}

	/**
	 * Add many relationships to the graph.
	 *
	 * @param Relationship[] $relationships
	 */
	public function addRelationships(array $relationships)
	{
		foreach ($relationships as $relationship) {
			$this->addRelationship($relationship);
		}
	}

	/**
	 * Add an entity to the graph.
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
	 * Add many entities to the graph.
	 *
	 * @param string[] $entities
	 */
	protected function addEntities(array $entities)
	{
		foreach ($entities as $name) {
			$this->addEntity($name);
		}
	}
}
