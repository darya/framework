<?php
namespace Darya\Service\Contracts;

/**
 * Darya's service provider interface.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Provider
{
	/**
	 * Register services with the given service container.
	 *
	 * @param Container $services
	 */
	public function register(Container $services);
}
