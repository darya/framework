<?php

namespace Darya\ORM\Relationship;

use Darya\ORM\EntityManager;
use Darya\ORM\Relationship;

/**
 * One-to-one relationship.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Has extends Relationship
{
	public function forParent($entity, EntityManager $orm): Relationship
	{
		$query = clone $this;

		$foreignKey = $this->getParentMap()->getStorageField($this->getForeignKey());
		$id         = $this->getParentId($entity);

		$query->where($foreignKey, $id);

		return $query;
	}

	public function forParents(array $entities, EntityManager $orm): Relationship
	{
		$query = clone $this;

		$foreignKey = $this->getParentMap()->getStorageField($this->getForeignKey());
		$ids        = $this->getParentIds($entities);

		$query->where("$foreignKey in", $ids);

		return $query;
	}

	public function match(array $parentEntities, array $relatedEntities): array
	{
		$parentMap  = $this->getParentMap();
		$primaryKey = $this->getParentMap()->getKey();

		// Index related entities by foreign key
		$relatedDictionary = $this->buildRelatedDictionary($relatedEntities);

		// Match related entities with their parents
		$relationshipName = $this->getName();

		foreach ($parentEntities as $parentEntity) {
			$parentId = $parentMap->readAttribute($parentEntity, $primaryKey);

			$parentMap->writeAttribute($parentEntity, $relationshipName, $relatedDictionary[$parentId] ?? null);
		}

		return $parentEntities;
	}

	/**
	 * Index related entities by their foreign keys.
	 *
	 * TODO: Dictionary helpers, somewhere.
	 *
	 * @param array $relatedEntities Related entities to index by their foreign keys.
	 * @return array Related entities indexed by their foreign keys.
	 */
	protected function buildRelatedDictionary(array $relatedEntities): array
	{
		$relatedMap = $this->getRelatedMap();
		$foreignKey = $this->getForeignKey();

		$relatedDictionary = [];

		foreach ($relatedEntities as $relatedEntity) {
			$parentId = $relatedMap->readAttribute($relatedEntity, $foreignKey);

			$relatedDictionary[$parentId] = $relatedEntity;
		}

		return $relatedDictionary;
	}
}
