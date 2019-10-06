<?php
namespace Darya\ORM\Relationship;

use Darya\ORM\Relationship;

class Has extends Relationship
{
	public function forParent($entity): Relationship
	{
		$query = clone $this;

		$foreignKey = $this->getForeignKey();
		$id = $this->getParentId($entity);

		$query->where($foreignKey, $id);

		return $query;
	}

	public function forParents(array $entities): Relationship
	{
		$query = clone $this;

		$foreignKey = $this->getForeignKey();
		$ids = $this->getParentIds($entities);

		$query->where("$foreignKey in", $ids);

		return $query;
	}

	public function match(array $parentEntities, array $relatedEntities)
	{
		$parentMap  = $this->getParentMap();
		$relatedMap = $this->getRelatedMap();
		$primaryKey = $this->getParentMap()->getKey();
		$foreignKey = $this->getForeignKey();

		// Key related entities by foreign key
		$relatedDictionary = [];

		foreach ($relatedEntities as $relatedEntity) {
			$parentId = $relatedMap->readAttribute($relatedEntity, $foreignKey);

			$relatedDictionary[$parentId] = $relatedEntity;
		}

		// Match related entities with parents
		$relationshipName = $this->getName();

		foreach ($parentEntities as $parentEntity) {
			$parentId = $parentMap->readAttribute($parentEntity, $primaryKey);

			$parentMap->writeAttribute($parentEntity, $relationshipName, $relatedDictionary[$parentId] ?? null);
		}

		return $parentEntities;
	}
}
