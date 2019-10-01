<?php

namespace Darya\ORM;

use Darya\Storage\Query;

/**
 * Darya's entity relationship class.
 *
 * Represents a relationship between two entities.
 *
 * Used to load related entities for parent entities.
 *
 * @property-read string    $name       The relationship name.
 * @property-read EntityMap $parentMap  The parent entity map.
 * @property-read EntityMap $relatedMap The related entity map.
 * @property-read string    $foreignKey The foreign key.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Relationship extends Query
{
	/**
	 * The relationship name.
	 *
	 * This should match the corresponding attribute on the parent entity.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The related entity map.
	 *
	 * @var EntityMap
	 */
	protected $relatedMap;

	/**
	 * The foreign key attribute.
	 *
	 * @var string
	 */
	protected $foreignKey;

	/**
	 * Create a new relationship.
	 *
	 * @param string    $name       The relationship name.
	 * @param EntityMap $parentMap  The parent entity map.
	 * @param EntityMap $relatedMap The related entity map.
	 * @param string    $foreignKey The foreign key attribute.
	 */
	public function __construct(string $name, EntityMap $parentMap, EntityMap $relatedMap, string $foreignKey = '')
	{
		parent::__construct($relatedMap->getName());

		$this->name       = $name;
		$this->parentMap  = $parentMap;
		$this->relatedMap = $relatedMap;
		$this->foreignKey = $foreignKey;
	}

	/**
	 * Build an instance of this relationship query for a given parent entity.
	 *
	 * @param mixed $entity The parent entity.
	 * @return Relationship The new relationship query.
	 */
	abstract public function forParent($entity): Relationship;

	/**
	 * Build an eager-loading instance of this relationship query for the given parent entities.
	 *
	 * @param mixed $entities The parent entities.
	 * @return Relationship The new relationship query.
	 */
	abstract public function eagerForParents(array $entities): Relationship;

	/**
	 * Match a set of eagerly-loaded related entities to the given parent entities.
	 *
	 * @param mixed[] $parentEntities  The parent entities to match.
	 * @param mixed[] $relatedEntities The related entities to match.
	 * @return mixed[] The parent entities with their related entities matched.
	 */
	abstract public function match(array $parentEntities, array $relatedEntities);

	/**
	 * Get the relationship name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get the parent entity map.
	 *
	 * @return EntityMap
	 */
	public function getParentMap(): EntityMap
	{
		return $this->parentMap;
	}

	/**
	 * Get the related entity map.
	 *
	 * @return EntityMap
	 */
	public function getRelatedMap(): EntityMap
	{
		return $this->relatedMap;
	}

	/**
	 * Get the foreign key.
	 *
	 * @return string
	 */
	public function getForeignKey(): string
	{
		return $this->foreignKey;
	}

	/**
	 * Get the parent entity's ID.
	 *
	 * @param mixed $parent
	 * @return mixed
	 */
	public function getParentId($parent)
	{
		// TODO: EntityMap should decide how to read attributes
		$parentKey = $this->getParentMap()->getKey();

		return $parent[$parentKey];
	}
}
