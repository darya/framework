<?php
namespace Darya\Service;

use Darya\Service\Container;

interface Provider {
	
	/**
	 * Register services with the given service container.
	 * 
	 * @param \Darya\Service\Container $services
	 */
	public function register(Container $services);
	
}
