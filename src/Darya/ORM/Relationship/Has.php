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

	public function eagerForParents(array $entities): Relationship
	{
		$query = clone $this;

		$foreignKey = $this->getForeignKey();
		$ids = [];

		// TODO: Extract this loop to a Relationship class method
		foreach ($entities as $entity) {
			$ids[] = $this->getParentId($entity);
		}

		$query->where("$foreignKey in", $ids);

		return $query;
	}

	public function match(array $parentEntities, array $relatedEntities)
	{
		$primaryKey = $this->getParentMap()->getKey();
		$foreignKey = $this->getForeignKey();

		// Key parent entities by foreign key
		$relatedDictionary = [];

		// TODO: EntityMap should decide how to read attributes ($relatedEntity[$foreignKey])
		foreach ($relatedEntities as $relatedEntity) {
			$relatedDictionary[$relatedEntity[$foreignKey]] = $relatedEntity;
		}

		// Match related entities with parents
		// TODO: EntityMap should decide how to read attributes ($parentEntity[$primaryKey])
		foreach ($parentEntities as $parentEntity) {
			$parentEntity[$this->getName()] = $relatedDictionary[$parentEntity[$primaryKey]] ?? null;
		}

		return $parentEntities;
	}
}
