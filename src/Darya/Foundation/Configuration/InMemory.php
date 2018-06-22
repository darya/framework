<?php
namespace Darya\Foundation\Configuration;

use Darya\Foundation\AbstractConfiguration;

/**
 * Darya's in-memory configuration implementation.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class InMemory extends AbstractConfiguration
{
	/**
	 * Build a new configuration from the given array of configuration data.
	 *
	 * @param array $data
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}
}
