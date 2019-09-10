<?php

namespace Darya\ORM\EntityMap;

/**
 * Darya's mapping strategy interface.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Strategy
{
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
