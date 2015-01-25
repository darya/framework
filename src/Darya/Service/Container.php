<?php
namespace Darya\Service;

use \Closure;
use \ReflectionClass;
use \ReflectionMethod;
use \ReflectionFunction;
use \ReflectionParameter;
use Darya\Service\ContainerException;
use Darya\Service\ContainerInterface;

/**
 * Darya's service container.
 * 
 * Service containers can be used to associate interfaces with implementations.
 * They make it painless to interchange the components of an application.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Container implements ContainerInterface {
	
	/**
	 * @var \Darya\Service\Container Singleton instance of the service container
	 */
	protected static $instance;
	
	/**
	 * @var array Set of interfaces as keys and implementations as values
	 */
	protected $services = array();
	
	/**
	 * @var array Set of aliases as keys and interfaces as values
	 */
	protected $aliases = array();
	
	/**
	 * Returns a singleton instance of the container.
	 * 
	 * @return \Darya\Service\Container
	 */
	public static function instance() {
		if (is_null(static::$instance)) {
			static::$instance = new static;
		}
		
		return static::$instance;
	}
	
	/**
	 * Instantiate a service container.
	 * 
	 * Registers the service container with itself, as well as registering any
	 * given services and aliases.
	 * 
	 * @param array $services [optional] Initial set of services and/or aliases
	 */
	public function __construct(array $services = array()) {
		$this->register(array(
			'Darya\Service\ContainerInterface' => $this,
			'Darya\Service\Container'          => $this
		));
		
		$this->register($services);
	}
	
	/**
	 * Enables shorter syntax for resolving services.
	 * 
	 * @param string $alias
	 * @return mixed
	 */
	public function __get($alias) {
		return $this->resolve($alias);
	}
	
	/**
	 * Enables shorter syntax for registering a service.
	 * 
	 * @param string $alias
	 * @param mixed  $service
	 */
	public function __set($alias, $service) {
		$this->register(array($alias => $service));
	}
	
	/**
	 * Determine whether the container has a service registered for the given
	 * interface or alias.
	 * 
	 * @param string $abstract
	 * @return bool
	 */
	public function has($abstract) {
		return isset($this->aliases[$abstract]) || isset($this->services[$abstract]);
	}
	
	/**
	 * Get the service associated with the given interface or alias.
	 * 
	 * This method does not resolve dependencies using registered services.
	 * 
	 * Returns null if not found.
	 * 
	 * @param string $abstract
	 * @return mixed
	 */
	public function get($abstract) {
		$abstract = isset($this->aliases[$abstract]) ? $this->aliases[$abstract] : $abstract;
		
		return isset($this->services[$abstract]) ? $this->services[$abstract] : null;
	}
	
	/**
	 * Register an interface and its associated implementation.
	 * 
	 * @param string $abstract
	 * @param mixed  $concrete
	 */
	public function set($abstract, $concrete) {
		$this->services[$abstract] = is_callable($concrete) ? $this->share($concrete) : $concrete;
	}
	
	/**
	 * Register an alias for the given interface.
	 * 
	 * @param string $alias
	 * @param string $abstract
	 */
	public function alias($alias, $abstract) {
		$this->aliases[$alias] = $abstract;
	}
	
	/**
	 * Register interfaces and their concrete implementations, or aliases and
	 * their corresponding interfaces.
	 * 
	 * This method only registers aliases if their interface is already
	 * registered with the container.
	 * 
	 * @param array $services interfaces => concretes or aliases => interfaces
	 */
	public function register(array $services = array()) {
		foreach ($services as $key => $value) {
			if (is_string($value) && isset($this->services[$value])) {
				$this->alias($key, $value);
			} else {
				$this->set($key, $value);
			}
		}
	}

	/**
	 * Resolve a service and its dependencies by interface or alias.
	 * 
	 * @param string $abstract  Interface or alias
	 * @param array  $arguments [optional]
	 * @return mixed
	 */
	public function resolve($abstract, array $arguments = array()) {
		$concrete = $this->get($abstract);
		
		if ($concrete instanceof Closure || is_callable($concrete)) {
			return $this->call($concrete, $arguments ?: array($this));
		} else if (is_string($concrete) && class_exists($concrete)) {
			return $this->create($concrete, $arguments);
		}
		
		return $concrete;
	}
	
	/**
	 * Wraps a callable in a closure that returns the same instance on every
	 * call using a static variable.
	 * 
	 * @param callable $callable
	 * @return \Closure
	 */
	public function share($callable) {
		if (is_callable($callable)) {
			$container = $this;
			
			return function () use ($callable, $container) {
				static $instance;
				
				if (is_null($instance)) {
					$instance = $container->call($callable, array($container));
				}
				
				return $instance;
			};
		}
		
		throw new ContainerException('Service is not callable in Container::share().');
	}
	
	/**
	 * Call a callable and attempt to resolve its parameters using services
	 * registered with the container.
	 * 
	 * @param callable $callable
	 * @param array    $arguments [optional]
	 * @return mixed
	 */
	public function call($callable, array $arguments = array()) {
		$method = is_array($callable) && count($callable) > 1 && method_exists($callable[0], $callable[1]);
		
		if ($method) {
			$reflection = new ReflectionMethod($callable[0], $callable[1]);
		} else {
			$reflection = new ReflectionFunction($callable);
		}
		
		$parameters = $reflection->getParameters();
		
		if (!array_map('is_numeric', array_keys($arguments))) {
			$arguments = array_merge($this->resolveParameters($parameters), $arguments);
		}
		
		return $method ? $reflection->invokeArgs($callable[0], $arguments) : $reflection->invokeArgs($arguments);
	}
	
	/**
	 * Instantiate the given class and attempt to resolve its constructor's
	 * parameters using services registered with the container.
	 * 
	 * @param string $class
	 * @param array  $arguments [optional]
	 * @return object
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
	 * @param \ReflectionParameter|null $parameter
	 * @return mixed
	 */
	protected function resolveParameter(ReflectionParameter $parameter) {
		try {
			$type = @$parameter->getClass()->name;
		} catch (\Exception $e) {
			$type = null;
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
