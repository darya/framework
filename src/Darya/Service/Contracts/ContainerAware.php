<?php
namespace Darya\Service\Contracts;

use Darya\Service\Contracts\Container;

/**
 * Implemented by classes that make use of a service container.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface ContainerAware {
	
	/**
	 * Set the service container.
	 * 
	 * @param \Darya\Service\Contracts\Container $container
	 */
	public function setServiceContainer(Container $container);
	
}
