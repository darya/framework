<?php
namespace Darya\Service\Contracts;

use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * Darya's application interface.
 * 
 * A service container that registers and boots service providers.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Application extends Container {
	
	/**
	 * Register a service provider with the application.
	 * 
	 * @param \Darya\Service\Contracts\Provider $provider
	 */
	public function provide(Provider $provider);
	
	/**
	 * Boot all registered service providers.
	 */
	public function boot();
	
}
