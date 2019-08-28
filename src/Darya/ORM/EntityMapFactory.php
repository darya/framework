<?php

namespace Darya\ORM;

use Darya\ORM\Strategy\PropertyStrategy;
use InvalidArgumentException;

/**
 * Darya's entity map factory.
 *
 * Provides simple entity map instantiation with sensible defaults.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class EntityMapFactory
{
	/**
	 * Create an entity map without a specific PHP class.
	 *
	 * @param string      $name
	 * @param array       $mapping
	 * @param string|null $resource
	 * @return EntityMap
	 */
	public function create(string $name, array $mapping, string $resource = null)
	{
		if ($resource === null) {
			// TODO: Extract snake_case resource name from given name class base name
			$resource = $name;
		}

		$entityMap = new EntityMap(
			Model::class, $resource, $mapping, new PropertyStrategy()
		);

		$entityMap->setName($name);

		return $entityMap;
	}

	/**
	 * Create a mapping for a PHP class.
	 *
	 * @param string      $class
	 * @param string|null $resource
	 * @param array|null  $mapping
	 * @return EntityMap
	 */
	public function createForClass(string $class, string $resource = null, array $mapping = null)
	{
		if (!class_exists($class)) {
			throw new InvalidArgumentException("Entity class '$class' cannot be mapped because it does not exist");
		}

		if ($resource === null) {
			// TODO: Extract snake_case resource name from class base name
			$resource = $class;
		}

		if (empty($mapping)) {
			// TODO: Extract class properties using PHP's Reflection API for a one-to-one mapping
			$mapping = [];
		}

		return new EntityMap(
			$class, $resource, $mapping, new PropertyStrategy()
		);
	}
}
