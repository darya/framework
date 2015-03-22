<?php
namespace Darya\Service;

use Darya\Service\Contracts\Container;

interface Provider {
	
	/**
	 * Register services with the given service container.
	 * 
	 * @param \Darya\Service\Contracts\Container $services
	 */
	public function register(Container $services);
	
}
