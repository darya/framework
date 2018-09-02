<?php
namespace Darya\Service;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use Darya\Service\Contracts\ContainerAware;
use Darya\Service\Contracts\Container as ContainerInterface;

/**
 * Darya's service container.
 *
 * Service containers can be used to associate interfaces with implementations.
 * They ease interchanging the components and dependencies of an application.
 *
 * TODO: ArrayAccess
 * TODO: factory() method
 * TODO: ContainerInterop: get() resolves dependencies, new raw() won't
 * TODO: Rename parameter resolution methods to parameter argument resolution methods for 0.6.0
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Container implements ContainerInterface
{
	/**
	 * Set of abstract service names as keys and implementations as values.
	 *
	 * @var array
	 */
	protected $services = [];

	/**
	 * Set of aliases as keys and interfaces as values.
	 *
	 * @var array
	 */
	protected $aliases = [];

	/**
	 * A delegate container to resolve services from.
	 *
	 * @var ContainerInterface
	 */
	protected $delegate;

	/**
	 * Instantiate a service container.
	 *
	 * Registers the service container with itself, as well as registering any
	 * given services and aliases.
	 *
	 * @param array $services [optional] Initial set of services and/or aliases
	 */
	public function __construct(array $services = [])
	{
		$this->register([
			'Darya\Service\Contracts\Container' => $this,
			'Darya\Service\Container'           => $this
		]);

		$this->register($services);
	}

	/**
	 * Dynamically resolve a service.
	 *
	 * @param string $abstract The abstract service name
	 * @return mixed The resolved service
	 * @throws ReflectionException
	 */
	public function __get($abstract)
	{
		return $this->resolve($abstract);
	}

	/**
	 * Dynamically register a service.
	 *
	 * @param string $abstract The abstract service name
	 * @param mixed  $service  The concrete service
	 */
	public function __set($abstract, $service)
	{
		$this->register([$abstract => $service]);
	}

	/**
	 * Determine whether the container has a service registered for the given
	 * abstract or alias.
	 *
	 * @param string $abstract The abstract service name
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
	 * @param string $abstract The abstract service name
	 * @return mixed The raw service
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

		if (isset($this->delegate)) {
			return $this->delegate->get($abstract);
		}

		return null;
	}

	/**
	 * Register a service and its associated implementation.
	 *
	 * @param string $abstract The abstract service name
	 * @param mixed  $concrete The concrete service
	 */
	public function set($abstract, $concrete)
	{
		$this->services[$abstract] = is_callable($concrete) ? $this->share($concrete) : $concrete;
	}

	/**
	 * Retrieve all registered services.
	 *
	 * @return array All registered services
	 */
	public function all()
	{
		return $this->services;
	}

	/**
	 * Register an alias for the given abstract.
	 *
	 * @param string $alias    The alias
	 * @param string $abstract The abstract service name
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
	public function register(array $services = [])
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
	 * @param string $abstract  Abstract service name or alias
	 * @param array  $arguments [optional] Arguments to resolve the service with
	 * @return mixed The resolved service
	 * @throws ReflectionException
	 */
	public function resolve($abstract, array $arguments = [])
	{
		$concrete = $this->get($abstract);

		if ($concrete instanceof Closure || is_callable($concrete)) {
			return $this->call($concrete, $arguments ?: [$this]);
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
	 * @param callable $callable The callable to wrap
	 * @return Closure
	 */
	public function share($callable)
	{
		if (!is_callable($callable)) {
			throw new InvalidArgumentException('Service is not callable');
		}

		$container = $this;

		return function () use ($callable, $container) {
			static $instance;

			if ($instance === null) {
				$instance = $container->call($callable, [$container]);
			}

			return $instance;
		};
	}

	/**
	 * Call a callable and attempt to resolve its parameters using services
	 * registered with the container.
	 *
	 * @param callable $callable  The callable to invoke
	 * @param array    $arguments [optional] The arguments to invoke the callable with
	 * @return mixed The return value of the callable's invocation
	 * @throws ReflectionException
	 */
	public function call($callable, array $arguments = [])
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
	 * @param string $class     The class to instantiate
	 * @param array  $arguments [optional] The arguments to instantiate the class with
	 * @return object The instantiated class
	 * @throws ReflectionException
	 */
	public function create($class, array $arguments = [])
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
	 * Delegate a container to resolve services from when this container is
	 * unable to.
	 *
	 * @param ContainerInterface $container The container to delegate
	 */
	public function delegate(ContainerInterface $container)
	{
		$this->delegate = $container;
	}

	/**
	 * Merge resolved parameters with the given arguments.
	 *
	 * TODO: Make this smarter.
	 *
	 * @param array $resolved  The resolved arguments
	 * @param array $arguments The given arguments
	 * @return array The merged arguments
	 */
	protected function mergeResolvedParameters(array $resolved, array $arguments = [])
	{
		if (!array_filter(array_keys($arguments), 'is_numeric')) {
			return array_merge($resolved, $arguments);
		} else {
			// Some alternate merge involving numeric indexes, maybe?
			return $arguments ?: $resolved;
		}
	}

	/**
	 * Resolve arguments for a set of reflection parameters.
	 *
	 * @param ReflectionParameter[] $parameters The parameters to resolve arguments for
	 * @param array                 $arguments  [optional] The given arguments
	 * @return array The resolved arguments keyed by parameter name
	 * @throws ReflectionException
	 */
	protected function resolveParameters($parameters, array $arguments = [])
	{
		$resolved = [];

		foreach ($parameters as $index => $parameter) {
			if (isset($arguments[$index])) {
				$resolved[$parameter->name] = $arguments[$index];
				continue;
			}

			if (isset($arguments[$parameter->name])) {
				$resolved[$parameter->name] = $arguments[$parameter->name];
				continue;
			}

			$argument = $this->resolveParameter($parameter);
			$resolved[$parameter->name] = $argument;
		}

		$resolved = $this->mergeResolvedParameters($resolved, $arguments);

		return $resolved;
	}

	/**
	 * Resolve an argument for a reflection parameter.
	 *
	 * @param ReflectionParameter|null $parameter The parameter to resolve an argument for
	 * @return mixed The resolved argument for the parameter
	 * @throws ReflectionException
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
	 * Resolve the class type hint of a reflection parameter.
	 *
	 * @param ReflectionParameter $parameter The parameter to resolve class type hint for
	 * @return string|null The class type hint of the reflection parameter
	 */
	protected function resolveParameterType(ReflectionParameter $parameter)
	{
		$class = $parameter->getClass();

		return is_object($class) ? $class->name : null;
	}
}
