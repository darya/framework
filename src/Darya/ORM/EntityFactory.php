<?php
namespace Darya\ORM;

/**
 * Darya's entity factory interface.
 *
 * Facilitates interchangeable entity instantiation methods.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface EntityFactory
{
	/**
	 * Create an entity with the given properties.
	 *
	 * @param array $attributes [optional] The attributes to create the entity with.
	 * @return mixed
	 */
	public function create(array $attributes = []);
}
