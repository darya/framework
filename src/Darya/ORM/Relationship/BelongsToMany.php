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
	 * The resource that contains relationships between the parent and related entities.
	 *
	 * Represents the equivalent of a junction table in SQL.
	 */
	protected string $junctionResource;

	public function __construct(
		string $name,
		EntityMap $parentMap,
		EntityMap $relatedMap,
		string $foreignKey,
		string $parentForeignKey,
		string $junctionResource
	) {
		parent::__construct($name, $parentMap, $relatedMap, $foreignKey);

		$this->parentForeignKey = $parentForeignKey;
		$this->junctionResource = $junctionResource;
	}

	public function forParent($entity): Relationship
	{
		// TODO: Implement forParent() method.
		$query = clone $this;

		return $query;
	}

	public function forParents(array $entities, EntityManager $orm): Relationship
	{
		$query = clone $this;

		// TODO: Be smarter than using default storage for junction queries
		//       Consider automapping junction entities where they're not, or forcing them to be provided, etc.
		$parentIds = $this->getParentIds($entities);
		$relatedIdsQuery = $orm->getDefaultStorage()
			->query($this->junctionResource)
			->fields([$this->foreignKey])
			->where("{$this->parentForeignKey} in", $parentIds);

		$relatedKey = $this->getRelatedMap()->getStorageKey();
		$query->where("$relatedKey in", $relatedIdsQuery);

		return $query;
	}

	public function match(array $parentEntities, array $relatedEntities): array
	{
		// TODO: Implement match() method.
		//       Find a sensible way to share junction entities with this method
		$parentIds = $this->getParentIds($parentEntities);
		$relatedIds = $this->getRelatedIds($relatedEntities);

		//var_dump($parentIds, $relatedIds);
		//die;

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
}