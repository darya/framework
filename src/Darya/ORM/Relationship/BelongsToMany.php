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

	public function match(array $parentEntities, array $relatedEntities): array
	{
		// TODO: Implement match() method.
		//       Load associative entities into memory explicitly and build a dictionary
		//       for matching
		//       @see \Darya\ORM\Relation\BelongsToMany::eager()
		$parentIds = $this->getParentIds($parentEntities);
		$relatedIds = $this->getRelatedIds($relatedEntities);

		var_dump($parentIds, $relatedIds);
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