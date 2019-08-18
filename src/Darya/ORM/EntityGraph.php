<?php

namespace Darya\ORM;

use RuntimeException;

/**
 * Darya's entity graph.
 *
 * Maintains relationships between mapped entity types.
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
	 * Entity mappers.
	 *
	 * Keyed by entity name.
	 *
	 * @var Mapper[]
	 */
	protected $mappers = [];

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
	 * @param Mapper[] $mappers    Entity mappers.
	 * @param Relation[]  $relationships Entity relationships.
	 */
	public function __construct(array $mappers = [], array $relationships = [])
	{
		$this->addMappers($mappers);
		$this->addRelationships($relationships);
	}

	/**
	 * Check whether the graph has an entity.
	 *
	 * @param string $entityName
	 * @return bool
	 */
	public function hasEntity(string $entityName): bool
	{
		return in_array($entityName, $this->entities);
	}

	/**
	 * Check whether the graph has a relationship.
	 *
	 * @param string $entityName
	 * @param string $relationshipName
	 * @return bool
	 */
	public function hasRelationship(string $entityName, string $relationshipName): bool
	{
		return isset($this->relationships[$entityName][$relationshipName]);
	}

	/**
	 * Get the mapper of an entity.
	 *
	 * @param string $entityName
	 * @return Mapper
	 */
	public function getMapper(string $entityName): Mapper
	{
		if (!$this->hasEntity($entityName)) {
			throw new RuntimeException("Entity '$entityName' not found");
		}

		return $this->mappers[$entityName];
	}

	/**
	 * Get a relationship of an entity.
	 *
	 * @param string $entityName       The entity name.
	 * @param string $relationshipName The relationship name.
	 * @return Relationship
	 * @throws RuntimeException
	 */
	public function getRelationship(string $entityName, string $relationshipName): Relationship
	{
		if (!$this->hasEntity($entityName)) {
			throw new RuntimeException("Entity '$entityName' not found");
		}

		if (!$this->hasRelationship($entityName, $relationshipName)) {
			throw new RuntimeException("Relationship '$relationshipName' not found for entity '$entityName'");
		}

		return $this->relationships[$entityName][$relationshipName];
	}

	/**
	 * Get all the relationships of an entity.
	 *
	 * @param string $entityName The entity name.
	 * @return Relationship[]
	 * @throws RuntimeException
	 */
	public function getRelationships($entityName)
	{
		if (!isset($this->relationships[$entityName])) {
			throw new RuntimeException("Entity '$entityName' not found");
		}

		return $this->relationships[$entityName];
	}

	/**
	 * Add an entity and its mapper to the graph.
	 *
	 * @param Mapper $mapper
	 */
	public function addMapper(Mapper $mapper)
	{
		$entityName = $mapper->getEntityMap()->getName();

		$this->addEntity($entityName);

		$this->mappers[$entityName] = $mapper;
	}

	/**
	 * Add many entities and their mappers to the graph.
	 *
	 * @param EntityMap[] $mappers
	 */
	public function addMappers(array $mappers)
	{
		foreach ($mappers as $map) {
			$this->addMapper($map);
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

		if (!isset($this->mappers[$name])) {
			$this->mappers[$name] = [];
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
