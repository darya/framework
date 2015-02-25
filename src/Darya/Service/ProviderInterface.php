<?php
namespace Darya\Service;

use Darya\Service\ContainerInterface;

interface ProviderInterface {
	
	/**
	 * Register services with the given service container.
	 * 
	 * @param \Darya\Service\ContainerInterface $services
	 */
	public function register(ContainerInterface $services);
	
}
