<?php
namespace Darya\ORM;

/**
 * A trait that implements the Mappable interface.
 *
 * @deprecated
 * @author Chris Andrew <chris@hexus.io>
 */
trait MappableTrait
{
	/**
	 * The entity's attribute data.
	 *
	 * @var array
	 */
	//protected $data = [];

	/**
	 * Set the raw attribute data of the mappable object.
	 *
	 * @param array $data The raw attribute data to set
	 * @return void
	 */
	public function setAttributeData(array $data = []): void
	{
		$this->data = $data;
	}

	/**
	 * Get the raw attribute data of the mappable object.
	 *
	 * @return array The raw attribute data
	 */
	public function getAttributeData(): array
	{
		return $this->data;
	}
}
