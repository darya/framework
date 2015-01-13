<?php
namespace Darya\Service;

use \Exception;
use Darya\Service\Container;

/**
 * Darya's service facade implementation, very similar to Laravel's approach.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Facade {
	
	/**
	 * @var Darya\Service\Container
	 */
	protected static $serviceContainer = null;
	
	public static function setServiceContainer(Container $container){
		static::$serviceContainer = $container;
	}
	
	public static function getServiceName(){
		$class = get_class(new static);
		throw new Exception('Facade "$class" does not implement getServiceName()');
	}
	
	public static function __callStatic($method, $parameters){
		$service = static::getServiceName();
		
		if (static::$serviceContainer) {
			$instance = static::$serviceContainer->resolve($service);
		} else {
			$instance = Container::instance()->resolve($service);
		}
		
		return call_user_func_array(array($instance, $method), $parameters);
	}
	
}
