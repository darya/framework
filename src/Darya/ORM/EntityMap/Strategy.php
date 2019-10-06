<?php

namespace Darya\ORM\EntityMap;

/**
 * Darya's entity attribute mapping strategy interface.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Strategy
{
	/**
	 * Read many attributes from an entity.
	 *
	 * @param object   $entity     The entity to read the attributes from.
	 * @param string[] $attributes The names of the attributes to read.
	 * @return mixed The attribute's value.
	 */
	public function readAttributes($entity, array $attributes);

	/**
	 * Write many attributes to an entity.
	 *
	 * @param object  $entity     The entity to write the attributes to.
	 * @param mixed[] $attributes The names and values of the attributes to write.
	 * @return void
	 */
	public function writeAttributes($entity, array $attributes): void;
}
