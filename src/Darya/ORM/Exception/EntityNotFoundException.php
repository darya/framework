<?php
namespace Darya\ORM\Exception;

use RuntimeException;

/**
 * Darya's entity not found exception.
 *
 * Thrown when a requested entity does not exist in storage.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class EntityNotFoundException extends RuntimeException
{
	/**
	 * The name of the affected Entity.
	 *
	 * @var string
	 */
	protected $entity;

	/**
	 * Get the name of the entity.
	 *
	 * @return string
	 */
	public function getEntityName()
	{
		return $this->entity;
	}

	/**
	 * Set the name of the entity.
	 *
	 * @param string $entity
	 * @return $this
	 */
	public function setEntityName($entity)
	{
		$this->entity = $entity;

		if (empty($this->message)) {
			$this->message = "{$this->entity} not found";
		}

		return $this;
	}
}
