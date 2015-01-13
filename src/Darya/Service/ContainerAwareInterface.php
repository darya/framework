<?php
namespace Darya\Service;

use Darya\Service\Container;

/**
 * Implemented by classes that make use of Darya's service container.
 * 
 * TODO: Use a ContainerInterface instead.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface ContainerAwareInterface {
	
	/**
	 * Set the service container.
	 * 
	 * @param Darya\Service\Container $container
	 */
	public function setServiceContainer(Container $container);
	
}
