<?php

namespace Darya\ORM\Strategy;

use Darya\ORM\Strategy;

/**
 * Darya's property mapping strategy.
 *
 * Maps data to object properties.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class PropertyStrategy implements Strategy
{
	/**
	 * Entity properties mapped to storage fields.
	 *
	 * @var array
	 */
	protected $mapping;

	/**
	 * Create a new property mapping strategy.
	 *
	 * @param array $mapping Entity properties mapped to storage fields.
	 */
	public function __construct(array $mapping)
	{
		$this->mapping = $mapping;
	}

	public function getStorageField(string $property): string
	{
		if (array_key_exists($property, $this->mapping)) {
			return $this->mapping[$property];
		}

		return $property;
	}

	public function mapToEntity($entity, array $data)
	{
		foreach ($this->mapping as $entityKey => $storageKey) {
			if (array_key_exists($storageKey, $data)) {
				$entity->{$entityKey} = $data[$storageKey];
			}
		}

		return $entity;
	}

	public function mapToStorage($entity): array
	{
		$data = [];

		foreach ($this->mapping as $entityKey => $storageKey) {
			$data[$storageKey] = $entity->{$entityKey};
		}

		return $data;
	}
}
