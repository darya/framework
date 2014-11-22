<?php
namespace Darya\Service;

/**
 * Darya's service container.
 * 
 * The service container can be used to associate interfaces with a concrete
 * implementation, making it easier to prepare for change in an application.
 * 
 * Service interface names do not need to be defined interfaces; they can simply
 * be string identifiers.
 * 
 * The alias feature of the container exists to map a given string identifier to 
 * a service interface that is registered with the container.
 * 
 * If a given concrete implementation is callable, it will be called when 
 * resolved from the container. The container will also attempt to resolve
 * any of the callable's type-hinted arguments.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Container {

	/**
	 * @var Darya\Service\Container Singleton instance of the container
	 */
	protected static $singleton = null;
	
	/**
	 * @var array Set of interfaces as keys and concrete implementations as values
	 */
	protected $services = array();
	
	/**
	 * @var array Set of aliases as keys and interfaces as values
	 */
	protected $aliases = array();
	
	/**
	 * Returns a singleton instance of the container.
	 * 
	 * @return Darya\Service\Container
	 */
	public static function instance() {
		return is_null(static::$singleton) ? static::$singleton = new static : static::$singleton;
	}
	
	/**
	 * Instantiate a service container.
	 * 
	 * @param array $services [optional] Optional initial set of services and/or aliases
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
	 * @param mixed  $service
	 */
	public function __set($id, $service) {
		$this->register($id, $service);
	}
	
	/**
	 * Register interfaces and their concrete implementations, or aliases and
	 * their corresponding interfaces.
	 * 
	 * @param array|string $key   Interface or alias, or set of interfaces => concretes, or set of aliases => interfaces
	 * @param mixed        $value Concrete implementation of the given interface, or interface to associate with the given alias
	 */
	public function register($key, $value = null) {
		if (is_array($key)) {
			$set = $key;
			
			foreach ($set as $key => $value) {
				$this->register($key, $value);
			}
		} else {
			if (is_string($value) && $this->interfaces[$value]) {
				$this->registerAlias($value);
			} else {
				$this->registerService($key, $value);
			}
		}
	}
	
	/**
	 * Register an interface and associated concrete implementation, or an 
	 * array of interfaces as keys and their corresponding concrete 
	 * implementations as values.
	 * 
	 * @param array|string $interface Interface or set of interfaces and concrete implementations
	 * @param mixed        $concrete  [optional] Concrete implementation to associate with the given interface
	 */
	public function registerService($interface, $concrete = null) {
		if (is_array($interface)) {
			$services = $interface;
			
			foreach ($services as $interface => $concrete) {
				$this->registerService($interface, $concrete);
			}
		} else {
			$this->services[$interface] = $concrete;
		}
	}
	
	/**
	 * Register an alias for the given interface, or an array of aliases as 
	 * keys.
	 * 
	 * @param array|string $alias     Service alias or set of aliases and interfaces
	 * @param mixed        $interface [optional] Interface to associate with the given alias
	 */
	public function registerAlias($alias, $interface = null) {
		if (is_array($alias)) {
			$aliases = $alias;
			
			foreach ($aliases as $alias => $interface) {
				$this->registerAlias($alias, $interface);
			}
		} else {
			$this->aliases[$alias] = $interface;
		}
	}

	/**
	 * Resolve a service by interface or alias.
	 * 
	 * TODO: Resolve callable service's type-hinted arguments using reflection.
	 * 
	 * @param string $service Service interface name or alias
	 * @return mixed
	 */
	public function resolve($service) {
		if (!isset($this->services[$service]))
			return null;
		
		$service = $this->services[$service];
		
		if ($service instanceof Closure || is_callable($service)) {
			return $service();
		}
		
		return $service;
	}
	
}
