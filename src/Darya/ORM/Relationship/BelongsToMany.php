<?php

namespace Darya\ORM\Relationship;

use Darya\ORM\EntityManager;
use Darya\ORM\EntityMap;
use Darya\ORM\Relationship;

/**
 * Many-to-many relationship.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class BelongsToMany extends Relationship
{
	/**
	 * Parent entity's key on the junction resource.
	 */
	protected string $parentForeignKey;

	/**
	 * The entity that represents relationships between the parent and related entities.
	 *
	 * Represents the equivalent of a junction table in SQL; one that contains foreign key tuples.
	 */
	protected string $associativeEntity;

	public function __construct(
		string $name,
		EntityMap $parentMap,
		EntityMap $relatedMap,
		string $foreignKey,
		string $parentForeignKey,
		string $associativeEntity
	) {
		parent::__construct($name, $parentMap, $relatedMap, $foreignKey);

		$this->parentForeignKey  = $parentForeignKey;
		$this->associativeEntity = $associativeEntity;
	}

	public function forParent($entity, EntityManager $orm): Relationship
	{
		$query = clone $this;

		$parentId = $this->getParentId($entity);
		$relatedIdsQuery = $orm->query($this->associativeEntity)
			->fields([$this->foreignKey])
			->where($this->parentForeignKey, $parentId);

		$relatedKey = $this->getRelatedKey();
		$query->where("$relatedKey in", $relatedIdsQuery);

		return $query;
	}

	public function forParents(array $entities, EntityManager $orm): Relationship
	{
		$query = clone $this;

		$parentIds = $this->getParentIds($entities);
		$relatedIdsQuery = $orm->query($this->associativeEntity)
			->fields([$this->foreignKey])
			->where("{$this->parentForeignKey} in", $parentIds);

		$relatedKey = $this->getRelatedKey();
		$query->where("$relatedKey in", $relatedIdsQuery);

		return $query;
	}

	public function match(array $parentEntities, array $relatedEntities, EntityManager $orm): array
	{
		$parentMap = $this->getParentMap();
		$adjacencyList = $this->buildAdjacencyList($parentEntities, $relatedEntities, $orm);

		$relationshipName = $this->getName();

		foreach ($parentEntities as $parentEntity) {
			$parentId = $this->getParentId($parentEntity);

			$parentMap->writeAttribute($parentEntity, $relationshipName, $adjacencyList[$parentId] ?? null);
		}

		return $parentEntities;
	}

	/**
	 * Check whether the parent mapping and related mapping use the same storage.
	 *
	 * @return bool
	 */
	protected function sameStorage(): bool
	{
		//return $this->getParentMap()->getStorage() === $this->getRelatedMap()->getStorage();
	}

	/**
	 * Build an adjacency list from parent entity IDs to lists of related entities.
	 *
	 * TODO: Actually use mapped field names
	 *
	 * @param array         $parentEntities
	 * @param array         $relatedEntities
	 * @param EntityManager $orm
	 * @return array
	 */
	protected function buildAdjacencyList(array $parentEntities, array $relatedEntities, EntityManager $orm): array
	{
		$parentIds = $this->getParentIds($parentEntities);

		// Build a dictionary of related entities (keyed by ID)
		$relatedDictionary = $this->buildRelatedDictionary($relatedEntities);

		// Load associations between parent and related entities
		$associations = $orm->query($this->associativeEntity)
			->fields([$this->parentForeignKey, $this->foreignKey])
			->where("{$this->parentForeignKey} in", $parentIds)
			->run();

		// Build an adjacency list as the related dictionary
		$adjacencyList = [];

		foreach ($associations as $association) {
			// Skip rows with missing IDs
			if (!isset($association[$this->parentForeignKey], $association[$this->foreignKey]))	{
				continue;
			}

			$parentId = $association[$this->parentForeignKey];
			$relatedId = $association[$this->foreignKey];

			$adjacencyList[$parentId][] = $relatedDictionary[$relatedId];
		}

		return $adjacencyList;
	}

	/**
	 * Build a dictionary of related entities, keyed by their ID.
	 *
	 * @param array $relatedEntities
	 * @return array
	 */
	protected function buildRelatedDictionary(array $relatedEntities): array
	{
		$relatedDictionary = [];

		foreach ($relatedEntities as $relatedEntity) {
			$relatedId = $this->getRelatedId($relatedEntity);

			$relatedDictionary[$relatedId] = $relatedEntity;
		}

		return $relatedDictionary;
	}
}