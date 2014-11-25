<?php
namespace Darya\Service;

use \ReflectionClass;
use \ReflectionFunction;
use \ReflectionParameter;
use Darya\Service\ContainerException;

/**
 * Darya's service container.
 * 
 * A service container can be used to associate interfaces with implementations;
 * the abstract with the concrete.
 * 
 * This makes it easier to interchange the components of an application without
 * modifying its source.
 * 
 * The alias feature of the container exists to map a given string to an
 * interface registered with the container.
 * 
 * If a given concrete implementation is callable, it will be called when
 * resolved from the container, and the container will also attempt to resolve
 * any of the callable's type-hinted arguments.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Container {
	
	/**
	 * @var Darya\Service\Container Singleton instance of the service container
	 */
	protected static $instance;
	
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
		return is_null(static::$instance) ? static::$instance = new static : static::$instance;
	}
	
	/**
	 * Instantiate a service container.
	 * 
	 * @param array $services [optional] Initial set of services and/or aliases
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
	 * Determine whether the container has a service registered for the given
	 * interface or alias.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has($key) {
		return isset($this->aliases[$key]) || isset($this->services[$key]);
	}
	
	/**
	 * Get the service associated with the given interface or alias.
	 * 
	 * Returns null if not found.
	 * 
	 * @return mixed
	 */
	public function get($key) {
		$key = isset($this->aliases[$key]) ? $this->aliases[$key] : $key;
		
		return isset($this->services[$key]) ? $this->services[$key] : null;
	}
	
	/**
	 * Associate a service with the given interface.
	 * 
	 * @param string $key
	 * @param mixed  $concrete
	 */
	public function set($key, $concrete) {
		$this->services[$interface] = $service;
	}
	
	/**
	 * Register interfaces and their concrete implementations, or aliases and
	 * their corresponding interfaces.
	 * 
	 * @param array|string $key   Interface or alias, or set of interfaces => concretes, or set of aliases => interfaces
	 * @param mixed        $value [optional] Implementation of the given interface, or interface to associate with the given alias
	 */
	public function register($key, $value = null) {
		if (is_array($key)) {
			$set = $key;
			
			foreach ($set as $key => $value) {
				$this->register($key, $value);
			}
		} else {
			if (is_string($value) && isset($this->services[$value])) {
				$this->registerAlias($key, $value);
			} else {
				$this->registerService($key, $value);
			}
		}
	}
	
	/**
	 * Register an interface and its associated implementation, or an array of
	 * interfaces as keys and their corresponding implementations as values.
	 * 
	 * @param array|string $interface Interface or set of interfaces and implementations
	 * @param mixed        $concrete  [optional] Implementation to associate with the given interface
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
	 * keys and interfaces as values.
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
	 * @param string $service   Interface name or alias
	 * @param array  $arguments [optional] Arguments to pass to closures or class constructors
	 * @return mixed
	 */
	public function resolve($interface, array $arguments = array()) {
		$concrete = $this->get($interface);
		
		if ($concrete instanceof Closure || is_callable($concrete)) {
			return $this->call($concrete, $arguments);
		} else if (is_string($concrete) && class_exists($concrete)) {
			return $this->create($concrete, $arguments);
		}
		
		return $concrete;
	}
	
	/**
	 * Call a callable and attempt to resolve its parameters using services
	 * registered with the container.
	 * 
	 * @param callable $callable
	 * @param array    $arguments [optional]
	 */
	public function call($callable, array $arguments = array()) {
		$reflection = new ReflectionFunction($concrete);
		
		$parameters = $reflection->getParameters();
		
		$arguments = array_merge($this->resolveParameters($parameters), $arguments);
		
		return $reflection->invokeArgs($arguments);
	}
	
	/**
	 * Instantiate the given class and attempt to resolve its constructor's
	 * parameters using services registered with the container.
	 * 
	 * @param callable $class
	 * @param array    $arguments [optional]
	 */
	public function create($class, array $arguments = array()) {
		$reflection = new ReflectionClass($class);
		
		$constructor = $reflection->getConstructor();
		
		if (!$constructor) {
			return $reflection->newInstance();
		}
		
		$parameters = $constructor->getParameters();
		
		$arguments = array_merge($this->resolveParameters($parameters), $arguments);
		
		return $reflection->newInstanceArgs($arguments);
	}
	
	/**
	 * Resolve a set of reflection parameters.
	 * 
	 * @param array $parameters
	 * @return array
	 */
	protected function resolveParameters($parameters) {
		$arguments = array();
		
		foreach ($parameters as $parameter) {
			$argument = $this->resolveParameter($parameter);
			
			$arguments[$parameter->name] = $argument;
		}
		
		return $arguments;
	}
	
	/**
	 * Attempt to resolve a reflection parameter from the container.
	 * 
	 * @param ReflectionParameter $parameter
	 * @return mixed
	 */
	protected function resolveParameter(ReflectionParameter $parameter) {
		try {
			$type = @$parameter->getClass()->name;
		} catch (\Exception $e) {
			
		}
		
		if ($type) {
			if (isset($this->services[$type])) {
				return $this->resolve($type);
			}
			
			if (class_exists($type)) {
				return $this->create($type);
			}
		}
		
		if ($parameter->isDefaultValueAvailable()) {
			return $parameter->getDefaultValue();
		}
		
		if ($parameter->allowsNull()) {
			return null;
		}
		
		throw new ContainerException("Could not resolve parameter: $parameter");
	}
	
}
