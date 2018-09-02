<?php
namespace Darya\ORM;

/**
 * An interface for the ORM's mappable objects.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Mappable
{
	/**
	 * Set the raw attribute data of the mappable object.
	 *
	 * @param array $data The raw attribute data to set
	 * @return void
	 */
	public function setAttributeData(array $data): void;

	/**
	 * Get the raw attribute data of the mappable object.
	 *
	 * @return array The raw attribute data
	 */
	public function getAttributeData(): array;
}
