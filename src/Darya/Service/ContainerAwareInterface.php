<?php
namespace Darya\Service;

use Darya\Service\ContainerInterface;

/**
 * Implemented by classes that make use of Darya's service container.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface ContainerAwareInterface {
	
	/**
	 * Set the service container.
	 * 
	 * @param \Darya\Service\ContainerInterface $container
	 */
	public function setServiceContainer(ContainerInterface $container);
	
}
