<?php
namespace Darya\Service;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use Darya\Service\ContainerException;
use Darya\Service\Contracts\ContainerAware;
use Darya\Service\Contracts\Container as ContainerInterface;

/**
 * Darya's service container.
 * 
 * Service containers can be used to associate interfaces with implementations.
 * They ease interchanging the components and dependencies of an application.
 * 
 * TODO: ArrayAccess
 * TODO: Delegate container
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Container implements ContainerInterface
{
	/**
	 * Set of abstracts as keys and implementations as values.
	 * 
	 * @var array
	 */
	protected $services = array();
	
	/**
	 * Set of aliases as keys and interfaces as values.
	 * 
	 * @var array
	 */
	protected $aliases = array();
	
	/**
	 * Instantiate a service container.
	 * 
	 * Registers the service container with itself, as well as registering any
	 * given services and aliases.
	 * 
	 * @param array $services [optional] Initial set of services and/or aliases
	 */
	public function __construct(array $services = array())
	{
		$this->register(array(
			'Darya\Service\Contracts\Container' => $this,
			'Darya\Service\Container'           => $this
		));
		
		$this->register($services);
	}
	
	/**
	 * Dynamically resolve a service.
	 * 
	 * @param string $abstract
	 * @return mixed
	 */
	public function __get($abstract)
	{
		return $this->resolve($abstract);
	}
	
	/**
	 * Dynamically register a service.
	 * 
	 * @param string $abstract
	 * @param mixed  $service
	 */
	public function __set($abstract, $service)
	{
		$this->register(array($abstract => $service));
	}
	
	/**
	 * Determine whether the container has a service registered for the given
	 * abstract or alias.
	 * 
	 * @param string $abstract
	 * @return bool
	 */
	public function has($abstract)
	{
		return isset($this->aliases[$abstract]) || isset($this->services[$abstract]);
	}
	
	/**
	 * Get the service associated with the given abstract or alias.
	 * 
	 * This method recursively resolves aliases but does not resolve service
	 * dependencies.
	 * 
	 * Returns null if nothing is found.
	 * 
	 * @param string $abstract
	 * @return mixed
	 */
	public function get($abstract)
	{
		if (isset($this->aliases[$abstract])) {
			$abstract = $this->aliases[$abstract];
			
			return $this->get($abstract);
		}
		
		if (isset($this->services[$abstract])) {
			return $this->services[$abstract];
		}
		
		return null;
	}
	
	/**
	 * Register a service and its associated implementation.
	 * 
	 * @param string $abstract
	 * @param mixed  $concrete
	 */
	public function set($abstract, $concrete)
	{
		$this->services[$abstract] = is_callable($concrete) ? $this->share($concrete) : $concrete;
	}
	
	/**
	 * Retrieve all registered services.
	 * 
	 * @return array
	 */
	public function all()
	{
		return $this->services;
	}
	
	/**
	 * Register an alias for the given abstract.
	 * 
	 * @param string $alias
	 * @param string $abstract
	 */
	public function alias($alias, $abstract)
	{
		$this->aliases[$alias] = $abstract;
	}
	
	/**
	 * Register services and aliases.
	 * 
	 * This method registers aliases if their abstract is already
	 * registered with the container.
	 * 
	 * @param array $services abstract => concrete and/or alias => abstract
	 */
	public function register(array $services = array())
	{
		foreach ($services as $key => $value) {
			if (is_string($value) && isset($this->services[$value])) {
				$this->alias($key, $value);
			} else {
				$this->set($key, $value);
			}
		}
	}

	/**
	 * Resolve a service and its dependencies.
	 * 
	 * This method recursively resolves services and aliases.
	 * 
	 * @param string $abstract  Abstract or alias
	 * @param array  $arguments [optional]
	 * @return mixed
	 */
	public function resolve($abstract, array $arguments = array())
	{
		$concrete = $this->get($abstract);
		
		if ($concrete instanceof Closure || is_callable($concrete)) {
			return $this->call($concrete, $arguments ?: array($this));
		}
		
		if (is_string($concrete)) {
			if ($abstract !== $concrete && $this->has($concrete)) {
				return $this->resolve($concrete, $arguments);
			}
			
			if (class_exists($concrete)) {
				return $this->create($concrete, $arguments);
			}
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
	public function share($callable)
	{
		if (!is_callable($callable)) {
			throw new ContainerException('Service is not callable in Container::share()');
		}
		
		$container = $this;
		
		return function () use ($callable, $container) {
			static $instance;
			
			if ($instance === null) {
				$instance = $container->call($callable, array($container));
			}
			
			return $instance;
		};
	}
	
	/**
	 * Call a callable and attempt to resolve its parameters using services
	 * registered with the container.
	 * 
	 * @param callable $callable
	 * @param array    $arguments [optional]
	 * @return mixed
	 */
	public function call($callable, array $arguments = array())
	{
		if (!is_callable($callable)) {
			return null;
		}
		
		$method = is_array($callable) && count($callable) > 1 && method_exists($callable[0], $callable[1]);
		
		if ($method) {
			$reflection = new ReflectionMethod($callable[0], $callable[1]);
		} else {
			$reflection = new ReflectionFunction($callable);
		}
		
		$parameters = $reflection->getParameters();
		$arguments = $this->resolveParameters($parameters, $arguments);
		
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
	public function create($class, array $arguments = array())
	{
		$reflection = new ReflectionClass($class);
		$constructor = $reflection->getConstructor();
		
		if (!$constructor) {
			return $reflection->newInstance();
		}
		
		$parameters = $constructor->getParameters();
		$arguments = $this->resolveParameters($parameters, $arguments);
		
		$instance = $reflection->newInstanceArgs($arguments);
		
		if ($instance instanceof ContainerAware) {
			$instance->setServiceContainer($this);
		}
		
		return $instance;
	}
	
	/**
	 * Merge resolved parameters with the given arguments.
	 * 
	 * TODO: Make this smarter.
	 * 
	 * @param array $resolved
	 * @param array $arguments
	 * @return array
	 */
	protected function mergeResolvedParameters(array $resolved, array $arguments = array())
	{
		if (!array_filter(array_keys($arguments), 'is_numeric')) {
			return array_merge($resolved, $arguments);
		} else {
			// Some alternate merge involving numeric indexes, maybe?
			return $arguments ?: $resolved;
		}
	}
	
	/**
	 * Resolve a set of reflection parameters.
	 * 
	 * @param ReflectionParameter[] $parameters
	 * @param array                 $arguments [optional]
	 * @return array
	 */
	protected function resolveParameters($parameters, array $arguments = array())
	{
		$resolved = array();
		
		foreach ($parameters as $parameter) {
			$argument = $this->resolveParameter($parameter);
			$resolved[$parameter->name] = $argument;
		}
		
		$resolved = $this->mergeResolvedParameters($resolved, $arguments);
		
		return $resolved;
	}
	
	/**
	 * Attempt to resolve a reflection parameter's argument.
	 * 
	 * @param ReflectionParameter|null $parameter
	 * @return mixed
	 */
	protected function resolveParameter(ReflectionParameter $parameter)
	{
		$type = $this->resolveParameterType($parameter);
		
		if ($type !== null) {
			if ($this->has($type)) {
				return $this->resolve($type);
			}
			
			if (class_exists($type)) {
				return $this->create($type);
			}
		}
		
		if ($parameter->isDefaultValueAvailable()) {
			return $parameter->getDefaultValue();
		}
		
		return null;
	}
	
	/**
	 * Resolve the given reflection parameters type hint.
	 * 
	 * @param ReflectionParameter $parameter
	 * @return string|null
	 */
	protected function resolveParameterType(ReflectionParameter $parameter)
	{
		$class = $parameter->getClass();
		
		return is_object($class) ? $class->name : null;
	}
}
