<?php
namespace Darya\Service;

use Darya\Service\Container;
use Darya\Service\Provider;

class Application extends Container {
	
	/**
	 * Register a service provider with the application.
	 * 
	 * @param \Darya\Service\Provider $provider
	 */
	public function provider(Provider $provider) {
		$provider->register($this);
	}
	
}
