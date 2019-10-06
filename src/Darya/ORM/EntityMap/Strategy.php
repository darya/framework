<?php

namespace Darya\ORM\EntityMap;

/**
 * Darya's mapping strategy interface.
 *
 * TODO: Move map methods to EntityMap
 * TODO: Change readAttribute() and writeAttribute() to readAttributes() and writeAttributes()
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Strategy
{
	/**
	 * Read an attribute from an entity.
	 *
	 * @param object $entity The entity to read the attribute from.
	 * @param string $attribute The name of the attribute to read.
	 * @return mixed The attribute's value.
	 */
	public function readAttribute($entity, string $attribute);

	/**
	 * Write an attribute to an entity.
	 *
	 * @param object $entity The entity to write the attribute to.
	 * @param string $attribute The name of the attribute to write.
	 * @param mixed $value The value of the attribute to write.
	 * @return void
	 */
	public function writeAttribute($entity, string $attribute, $value): void;

	/**
	 * Map from storage data to an entity.
	 *
	 * @param object $entity      The entity to map to.
	 * @param array  $mapping     The mapping of entity fields to storage fields.
	 * @param array  $storageData The storage data to map from.
	 * @return object The mapped entity.
	 */
	public function mapFromStorage($entity, array $mapping, array $storageData);

	/**
	 * Map from an entity to storage data.
	 *
	 * @param object $entity  The entity to map from.
	 * @param array  $mapping The mapping of entity fields to storage fields.
	 * @return array The mapped storage data.
	 */
	public function mapToStorage($entity, array $mapping): array;
}
