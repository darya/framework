<?php
namespace Darya\Service\Contracts;

use Closure;
use Darya\Service\Exceptions\ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Darya's service container interface.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Container extends ContainerInterface
{
	/**
	 * Determine whether the container has a service registered for the given
	 * abstract or alias.
	 *
	 * @param string $abstract The abstract service name
	 * @return bool
	 */
	public function has($abstract);

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
	public function raw($abstract);

	/**
	 * Resolve a service and its dependencies.
	 *
	 * This method recursively resolves services and aliases.
	 *
	 * @param string $abstract  Abstract service name or alias
	 * @param array  $arguments [optional] Arguments to resolve the service with
	 * @throws ContainerException Error resolving the service
	 * @return mixed The resolved service
	 */
	public function get($abstract, array $arguments = []);

	/**
	 * Register a service and its associated implementation.
	 *
	 * @param string $abstract The abstract service name
	 * @param mixed  $concrete The concrete service
	 */
	public function set($abstract, $concrete);

	/**
	 * Register an alias for the given abstract.
	 *
	 * @param string $alias    The alias
	 * @param string $abstract The abstract service name
	 */
	public function alias($alias, $abstract);

	/**
	 * Register services and aliases.
	 *
	 * This method registers aliases if their abstract is already
	 * registered with the container.
	 *
	 * @param array $services [abstract => concrete] and/or [alias => abstract]
	 */
	public function register(array $services);

	/**
	 * Wrap a callable in a closure that returns the same instance on every
	 * call using a static variable.
	 *
	 * @param callable $callable The callable to wrap
	 * @return Closure
	 */
	public function share($callable);

	/**
	 * Call a callable and attempt to resolve its parameters using services
	 * registered with the container.
	 *
	 * @param callable $callable  The callable to invoke
	 * @param array    $arguments [optional] The arguments to invoke the callable with
	 * @return mixed The return value of the callable's invocation
	 * @throws ContainerException
	 */
	public function call($callable, array $arguments = array());

	/**
	 * Instantiate the given class and attempt to resolve its constructor's
	 * parameters using services registered with the container.
	 *
	 * @param string $class     The class to instantiate
	 * @param array  $arguments [optional] The arguments to instantiate the class with
	 * @return object The instantiated class
	 * @throws ContainerException
	 */
	public function create($class, array $arguments = array());
}
