<?php
namespace Darya\Service;

use Darya\Service\Contracts\Application as ApplicationInterface;
use Darya\Service\Contracts\Provider;

/**
 * Darya's application implementation.
 * 
 * A service container that registers and boots service providers.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Application extends Container implements ApplicationInterface
{
	/**
	 * Set of service providers registered with the application.
	 * 
	 * @var array
	 */
	protected $providers = array();
	
	/**
	 * Instantiate an application.
	 * 
	 * @param array $services [optional] Initial set of services and/or aliases
	 */
	public function __construct(array $services = array())
	{
		$this->register(array(
			'Darya\Service\Contracts\Application' => $this,
			'Darya\Service\Application'           => $this
		));
		
		parent::__construct($services);
	}
	
	/**
	 * Register a service provider with the application.
	 * 
	 * @param Provider $provider
	 */
	public function provide(Provider $provider)
	{
		$this->providers[] = $provider;
		$provider->register($this);
	}
	
	/**
	 * Boot all registered service providers.
	 * 
	 * TODO: Bootable interface.
	 */
	public function boot()
	{
		foreach ($this->providers as $provider) {
			if (method_exists($provider, 'boot')) {
				$this->call(array($provider, 'boot'));
			}
		}
	}
}
