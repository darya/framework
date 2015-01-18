<?php
namespace Darya\Service;

use \Exception;
use Darya\Service\Container;
use Darya\Service\ContainerInterface;

/**
 * Darya's service facade implementation, very similar to Laravel's approach.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Facade {
	
	/**
	 * @var \Darya\Service\ContainerInterface
	 */
	protected static $serviceContainer;
	
	/**
	 * Set the service container to use for all facades.
	 * 
	 * @param \Darya\Service\ContainerInterface $container
	 */
	public static function setServiceContainer(ContainerInterface $container){
		static::$serviceContainer = $container;
	}
	
	/**
	 * Return the service interface or alias to resolve from the container.
	 * 
	 * All facades must override this method.
	 * 
	 * @return string
	 */
	public static function getServiceName(){
		$class = get_class(new static);
		throw new Exception('Facade "' . $class . '" does not implement getServiceName()');
	}
	
	/**
	 * Magic method that redirects static calls to the facade's related service.
	 * 
	 * @param string $method
	 * @param array  $parameters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters){
		$service = static::getServiceName();
		
		if (!static::$serviceContainer) {
			static::$serviceContainer = Container::instance();
		}
		
		$instance = static::$serviceContainer->resolve($service);
		
		return static::$serviceContainer->call(array($instance, $method), $parameters);
	}
	
}
