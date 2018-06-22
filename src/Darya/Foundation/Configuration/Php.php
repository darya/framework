<?php
namespace Darya\Foundation\Configuration;

use Darya\Foundation\AbstractConfiguration;

/**
 * Darya's PHP file configuration implementation.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Php extends AbstractConfiguration
{
	/**
	 * Build a new configuration from the PHP files at the given paths.
	 *
	 * @param array|string $paths
	 */
	public function __construct($paths)
	{
		$data = array();
		$paths = (array) $paths;

		foreach ($paths as $path) {
			if (is_file($path) && preg_match('/\.php$/', $path)) {
				$data = array_merge($data, include $path ?: array());
			}
		}

		$this->data = $data;
	}
}
