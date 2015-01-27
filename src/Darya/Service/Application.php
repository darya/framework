<?php
namespace Darya\Service;

use Darya\Service\ApplicationInterface;
use Darya\Service\ProviderInterface;

/**
 * Darya's application implementation.
 * 
 * A service container that registers and boots service providers.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Application extends Container implements ApplicationInterface {
	
	/**
	 * @var array Set of service providers registered with the application
	 */
	protected $providers = array();
	
	/**
	 * Register a service provider with the application.
	 * 
	 * @param \Darya\Service\ProviderInterface $provider
	 */
	public function provide(ProviderInterface $provider) {
		$this->providers[] = $provider;
		$provider->register($this);
	}
	
	/**
	 * Boot all registered service providers.
	 */
	public function boot() {
		foreach ($this->providers as $provider) {
			if (method_exists($provider, 'boot')) {
				$this->call(array($provider, 'boot'));
			}
		}
	}
	
}
