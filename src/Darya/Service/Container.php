<?php

namespace Darya\Service;

use Closure;
use Darya\Service\Exceptions\ContainerException;
use Darya\Service\Exceptions\NotFoundException;
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
	 * @throws ContainerException
	 */
	public function __get($abstract)
	{
		return $this->get($abstract);
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

	public function has($abstract)
	{
		return isset($this->aliases[$abstract]) || isset($this->services[$abstract]);
	}

	public function raw($abstract)
	{
		if (isset($this->aliases[$abstract])) {
			$abstract = $this->aliases[$abstract];

			return $this->raw($abstract);
		}

		if (isset($this->services[$abstract])) {
			return $this->services[$abstract];
		}

		if (isset($this->delegate)) {
			return $this->delegate->raw($abstract);
		}

		throw new NotFoundException("Service '$abstract' not found");
	}

	public function get($abstract, array $arguments = [])
	{
		$concrete = $this->raw($abstract);

		try {
			if ($concrete instanceof Closure || is_callable($concrete)) {
				return $this->call($concrete, $arguments ?: [$this]);
			}

			if (is_string($concrete)) {
				if ($abstract !== $concrete && $this->has($concrete)) {
					return $this->get($concrete, $arguments);
				}

				if (class_exists($concrete)) {
					return $this->create($concrete, $arguments);
				}
			}
		} catch (ContainerException $exception) {
			throw new ContainerException(
				"Error resolving service '$abstract'",
				$exception->getCode(),
				$exception
			);
		}

		return $concrete;
	}

	public function set($abstract, $concrete)
	{
		$this->services[$abstract] = is_callable($concrete) ? $this->share($concrete) : $concrete;
	}

	public function alias($alias, $abstract)
	{
		$this->aliases[$alias] = $abstract;
	}

	public function register(array $services)
	{
		foreach ($services as $key => $value) {
			if (is_string($value) && isset($this->services[$value])) {
				$this->alias($key, $value);
			} else {
				$this->set($key, $value);
			}
		}
	}

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

	public function call($callable, array $arguments = [])
	{
		if (!is_callable($callable)) {
			throw new ContainerException("Callable given is not callable");
		}

		$method = is_array($callable) && count($callable) > 1 && method_exists($callable[0], $callable[1]);

		try {
			if ($method) {
				$reflection = new ReflectionMethod($callable[0], $callable[1]);
			} else {
				$reflection = new ReflectionFunction($callable);
			}

			$parameters = $reflection->getParameters();
			$arguments  = $this->resolveParameterArguments($parameters, $arguments);
		} catch (ReflectionException $exception) {
			throw new ContainerException(
				"Error calling callable",
				$exception->getCode(),
				$exception
			);
		}

		return $method ? $reflection->invokeArgs($callable[0], $arguments) : $reflection->invokeArgs($arguments);
	}

	public function create($class, array $arguments = [])
	{
		try {
			$reflection  = new ReflectionClass($class);
			$constructor = $reflection->getConstructor();

			if (!$constructor) {
				return $reflection->newInstance();
			}

			$parameters = $constructor->getParameters();
			$arguments  = $this->resolveParameterArguments($parameters, $arguments);
		} catch (ReflectionException $exception) {
			throw new ContainerException(
				"Error creating instance of '$class'",
				$exception->getCode(),
				$exception
			);
		}

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
	 * Merge resolved parameters arguments with the given arguments.
	 *
	 * TODO: Make this smarter.
	 *
	 * @param array $resolved  The resolved arguments
	 * @param array $arguments The given arguments
	 * @return array The merged arguments
	 */
	protected function mergeResolvedParameterArguments(array $resolved, array $arguments = [])
	{
		if (empty(array_filter(array_keys($arguments), 'is_numeric'))) {
			// We can perform a simple array merge if there are no numeric keys
			return array_merge($resolved, $arguments);
		}

		// Otherwise, we use the given arguments, falling back to resolved arguments
		// TODO: Some alternate merge involving numeric indexes, maybe?
		return $arguments ?: $resolved;
	}

	/**
	 * Resolve arguments for a set of reflection parameters.
	 *
	 * @param ReflectionParameter[] $parameters The parameters to resolve arguments for
	 * @param array                 $arguments  [optional] The given arguments
	 * @return array The resolved arguments keyed by parameter name
	 * @throws ContainerException
	 */
	protected function resolveParameterArguments(array $parameters, array $arguments = [])
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

			$argument                   = $this->resolveParameterArgument($parameter);
			$resolved[$parameter->name] = $argument;
		}

		$resolved = $this->mergeResolvedParameterArguments($resolved, $arguments);

		return $resolved;
	}

	/**
	 * Resolve an argument for a reflection parameter.
	 *
	 * @param ReflectionParameter|null $parameter The parameter to resolve an argument for
	 * @return mixed The resolved argument for the parameter
	 * @throws ContainerException
	 */
	protected function resolveParameterArgument(ReflectionParameter $parameter)
	{
		$type = $this->resolveParameterType($parameter);

		if ($type !== null) {
			if ($this->has($type)) {
				return $this->get($type);
			}

			if (class_exists($type)) {
				return $this->create($type);
			}
		}

		if ($parameter->isDefaultValueAvailable()) {
			try {
				return $parameter->getDefaultValue();
			} catch (ReflectionException $exception) {
				// We want to continue to the exception below
				// when a default value cannot be resolved
			}
		}

		throw new ContainerException("Unresolvable parameter '\${$parameter->name}'");
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
