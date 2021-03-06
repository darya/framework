<?php
namespace Darya\Service\Contracts;

/**
 * Darya's application interface.
 *
 * A service container that registers and boots service providers.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Application extends Container
{
	/**
	 * Register a service provider with the application.
	 *
	 * @param Provider $provider
	 */
	public function provide(Provider $provider);

	/**
	 * Boot all registered service providers.
	 */
	public function boot();
}
