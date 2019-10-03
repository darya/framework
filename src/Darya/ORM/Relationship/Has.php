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
		// TODO: Implement match() method.
		$relatedDictionary = [];

		// Key parent entities by foreign key
		$foreignKey = $this->getForeignKey();

		foreach ($relatedEntities as $relatedEntity) {
			$relatedDictionary[$relatedEntity[$foreignKey]] = $relatedEntity;
		}

		// Match related entities with parents
		$primaryKey = $this->getParentMap()->getKey();

		//var_dump($this->getName(), $relatedDictionary);die;

		foreach ($parentEntities as $parentEntity) {
			if (isset($relatedDictionary[$parentEntity[$primaryKey]])) {
				$parentEntity[$this->getName()] = $relatedDictionary[$parentEntity[$primaryKey]];
			}
		}

		return $parentEntities;
	}
}
