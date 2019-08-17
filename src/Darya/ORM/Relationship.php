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
	 * @var string
	 */
	protected $name;

	/**
	 * @var EntityMap
	 */
	protected $parentMap;

	/**
	 * @var EntityMap
	 */
	protected $relatedMap;

	/**
	 * @var string
	 */
	protected $foreignKey;

	/**
	 * Create a new relationship.
	 *
	 * @param string    $name       The relationship name.
	 * @param EntityMap $parentMap  The parent entity map.
	 * @param EntityMap $relatedMap The related entity map.
	 * @param string    $foreignKey The foreign key.
	 */
	public function __construct(string $name, EntityMap $parentMap, EntityMap $relatedMap, string $foreignKey = '')
	{
		parent::__construct($relatedMap->getResource());

		$this->name       = $name;
		$this->parentMap  = $parentMap;
		$this->relatedMap = $relatedMap;
		$this->foreignKey = $foreignKey;
	}

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
}
