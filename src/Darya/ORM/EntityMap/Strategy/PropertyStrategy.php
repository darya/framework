<?php

namespace Darya\ORM\EntityMap\Strategy;

use Darya\ORM\EntityMap\Strategy;

/**
 * Darya's property mapping strategy.
 *
 * Maps data to object properties.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class PropertyStrategy implements Strategy
{
	public function mapFromStorage($entity, array $mapping, array $storageData)
	{
		foreach ($mapping as $entityKey => $storageKey) {
			if (array_key_exists($storageKey, $storageData)) {
				$entity->{$entityKey} = $storageData[$storageKey];
			}
		}

		return $entity;
	}

	public function mapToStorage($entity, array $mapping): array
	{
		$data = [];

		foreach ($mapping as $entityKey => $storageKey) {
			$data[$storageKey] = $entity->{$entityKey};
		}

		return $data;
	}
}
