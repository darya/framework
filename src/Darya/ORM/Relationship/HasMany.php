<?php

namespace Darya\ORM\Relationship;

/**
 * HasMany class.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class HasMany extends Has
{
	protected function buildRelatedDictionary(array $relatedEntities): array
	{
		$relatedMap = $this->getRelatedMap();
		$foreignKey = $this->getForeignKey();

		$relatedDictionary = [];

		foreach ($relatedEntities as $relatedEntity) {
			$parentId = $relatedMap->readAttribute($relatedEntity, $foreignKey);

			if (!isset($relatedDictionary[$parentId])) {
				$relatedDictionary[$parentId] = [];
			}

			$relatedDictionary[$parentId][] = $relatedEntity;
		}

		return $relatedDictionary;
	}
}
