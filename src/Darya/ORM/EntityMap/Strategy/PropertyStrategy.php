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
	/**
	 * @param object   $entity
	 * @param string[] $attributes
	 * @return array|mixed
	 */
	public function readAttributes($entity, array $attributes)
	{
		$values = [];

		foreach ($attributes as $attribute) {
			$values[$attribute] = $entity->{$attribute};
		}

		return $values;
	}

	public function writeAttributes($entity, array $attributes): void
	{
		foreach ($attributes as $attribute => $value) {
			$entity->{$attribute} = $value;
		}
	}
}
