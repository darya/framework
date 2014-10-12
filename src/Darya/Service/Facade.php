<?php
namespace Darya\Service;

use Darya\Service\Container;

/**
 * Darya's service facade implementation, very similar to Laravel's approach.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Facade {
	
	protected static $serviceContainer = null;
	
	public static function setServiceContainer(Container $container){
		static::$serviceContainer = $container;
	}
	
	public static function getServiceName(){
		throw new Exception('Facade does not implement getServiceName()');
	}
	
	public static function __callStatic($method, $parameters){
		$service = static::getServiceName();
		
		if (static::$serviceContainer) {
			$instance = static::$serviceContainer->resolve($service);
		} else {
			$instance = Services::instance()->resolve($service);
		}
		
		return call_user_func_array(array($instance, $method), $parameters);
	}
	
}
