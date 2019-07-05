<?php

namespace Darya\ORM;

/**
 * Darya's mapping strategy interface.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Strategy
{
	/**
	 * Get the storage field that maps to the given entity property.
	 *
	 * TODO: Not really sure about this method
	 *
	 * @param string $property The entity property to get the storage field for.
	 * @return string The storage field.
	 */
	public function getStorageField(string $property): string;

	/**
	 * Map storage data to an entity.
	 *
	 * @param object $entity The entity to map to.
	 * @param array $data   The data to map from.
	 * @return object The mapped entity.
	 */
	public function mapToEntity($entity, array $data);

	/**
	 * Map an entity to storage data.
	 *
	 * @param object $entity The entity to map from.
	 * @return array The mapped storage data.
	 */
	public function mapToStorage($entity): array;
}
