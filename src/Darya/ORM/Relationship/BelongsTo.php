<?php

namespace Darya\ORM\Relationship;

use Darya\ORM\EntityManager;
use Darya\ORM\Relationship;

/**
 * Inverse one-to-one relationship.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class BelongsTo extends Relationship
{
	public function forParent($entity, EntityManager $orm): Relationship
	{
		$query = clone $this;

		$relatedKey = $this->getRelatedMap()->getStorageKey();
		$relatedId  = $this->getParentMap()->readAttribute($entity, $this->getForeignKey());

		$query->where("$relatedKey =", $relatedId);

		return $query;
	}

	public function forParents(array $entities, EntityManager $orm): Relationship
	{
		$query = clone $this;

		$relatedKey = $this->getRelatedMap()->getStorageKey();
		$relatedIds = $this->getParentMap()->readAttributeFromMany($entities, $this->getForeignKey());

		$query->where("$relatedKey in", $relatedIds);

		return $query;
	}

	public function match(array $parentEntities, array $relatedEntities, EntityManager $orm): array
	{
		$parentMap  = $this->getParentMap();
		$foreignKey = $this->getForeignKey();

		// Index related entities by foreign key
		$relatedDictionary = $this->buildRelatedDictionary($relatedEntities);

		// Match related entities with their parents
		$relationshipName = $this->getName();

		foreach ($parentEntities as $parentEntity) {
			$relatedId = $parentMap->readAttribute($parentEntity, $foreignKey);

			$parentMap->writeAttribute($parentEntity, $relationshipName, $relatedDictionary[$relatedId] ?? null);
		}

		return $parentEntities;
	}

	/**
	 * Index related entities by their primary keys.
	 *
	 * TODO: Dictionary helpers, somewhere.
	 *
	 * @param array $relatedEntities Related entities to index by their primary keys.
	 * @return array Related entities indexed by their primary keys.
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
