<?php
namespace Darya\Service;

use Darya\Service\ContainerInterface;
use Darya\Service\ProviderInterface;

/**
 * Darya's application interface.
 * 
 * A service container that registers and boots service providers.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface ApplicationInterface extends ContainerInterface {
	
	/**
	 * Register a service provider with the application.
	 * 
	 * @param \Darya\Service\ProviderInterface $provider
	 */
	public function provide(ProviderInterface $provider);
	
	/**
	 * Boot all registered service providers.
	 */
	public function boot();
	
}
