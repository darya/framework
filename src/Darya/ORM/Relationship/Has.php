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

	public function eagerForParents($entities): Relationship
	{
		$query = clone $this;

		$foreignKey = $this->getForeignKey();
		$ids = [];

		// TODO: Extract to parent class method
		foreach ($entities as $entity) {
			$ids[] = $this->getParentId($entity);
		}

		$query->where("$foreignKey in", $ids);

		return $query;
	}
}
