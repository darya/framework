<?php
namespace Darya\Service;

/**
 * Service container for Darya.
 * 
 * TODO: Reflection; ability register interfaces to be resolved. "Services IDs"
 *       would subsequently become aliases to these interfaces.
 * 
 * @author Chris Andrew
 */
class Container {

	protected static $singleton = null;
	protected $services = null;
	protected $aliases = array();
	
	/**
	 * Returns a singleton instance of the Container.
	 * 
	 * @return Darya\Service\Container
	 */
	public static function instance() {
		return is_null(static::$singleton) ? static::$singleton = new static : static::$singleton;
	}
	
	/**
	 * Instantiate a service container.
	 * 
	 * @param array $services [optional]
	 */
	public function __construct(array $services = array()) {
		$this->register($services);
	}
	
	/**
	 * Enables shorter syntax for resolving services.
	 * 
	 * @param string $id
	 * @return mixed
	 */
	public function __get($id) {
		if (!property_exists($this, $id)) {
			return $this->resolve($id);
		}
		
		return $this->$id;
	}
	
	/**
	 * Enables shorter syntax for registering a service.
	 * 
	 * @param string $id
	 * @param mixed $service
	 */
	public function __set($id, $service) {
		$this->register($id, $service);
	}
	
	/**
	 * Register a service or a list of services.
	 * 
	 * @param array|string $id Service identifier or list of services
	 * @param mixed $service [optional]
	 */
	public function register($id, $service = null) {
		if (is_array($id)) {
			$services = $id;
			
			foreach ($services as $id => $service) {
				$this->services[$id] = $service;
			}
		} else {
			$this->services[$id] = $service;
		}
	}

	/**
	 * Resolve a service.
	 * 
	 * @param string $id Service identifier
	 * @return mixed
	 */
	public function resolve($id) {
		$service = $this->services[$id];
		
		if ($service instanceof Closure || is_callable($service)) {
			return $service();
		}
		
		return $service;
	}
	
}
