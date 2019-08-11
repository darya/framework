<?php

namespace Darya\ORM;

/**
 * Darya's entity graph.
 *
 * Maintains relationships between different entity types.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class EntityGraph
{
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
	private $relationships;

	/**
	 * Create a new entity graph.
	 *
	 * @param array $maps          Entity maps.
	 * @param array $relationships Entity relationships.
	 */
	public function __construct(array $maps, array $relationships)
	{
		$this->addMaps($maps);
		$this->relationships = $relationships;
	}

	public function addMap(EntityMap $map)
	{
		$entityName = $map->getClass();

		if (!isset($this->maps[$entityName])) {
			$this->maps[$entityName] = [];
		}

		$this->maps[$entityName][] = $map;
	}

	public function addMaps(array $maps)
	{
		foreach ($maps as $map) {
			$this->addMap($map);
		}
	}

	public function addRelationship(Relation $relationship)
	{
		// TODO: Class name should be provided by the relationship class
		$entityName = get_class($relationship->parent);

		if (!isset($this->relationships[$entityName])) {
			$this->relationships[$entityName] = [];
		}

		$this->relationships[$entityName][] = $relationship;
	}

	public function addRelationships(array $relationships)
	{
		foreach ($relationships as $relationship) {
			$this->addRelationship($relationship);
		}
	}
}
