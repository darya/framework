<?php
namespace Darya\Service;

/**
 * Darya's service container interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface ContainerInterface {
	
	/**
	 * Determine whether the container has a service registered for the given
	 * interface or alias.
	 * 
	 * @param string $abstract
	 * @return bool
	 */
	public function has($abstract);
	
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
	public function get($abstract);
	
	/**
	 * Register an interface and its associated implementation.
	 * 
	 * @param string $abstract
	 * @param mixed  $concrete
	 */
	public function set($abstract, $concrete);
	
	/**
	 * Register an alias for the given interface.
	 * 
	 * @param string $alias
	 * @param string $abstract
	 */
	public function alias($alias, $abstract);
	
	/**
	 * Register interfaces and their concrete implementations, or aliases and
	 * their corresponding interfaces.
	 * 
	 * This method only registers aliases if their interface is already
	 * registered with the container.
	 * 
	 * @param array $services interfaces => concretes or aliases => interfaces
	 */
	public function register(array $services = array());
	
	/**
	 * Resolve a service and its dependencies by interface or alias.
	 * 
	 * @param string $abstract  Interface or alias
	 * @param array  $arguments [optional]
	 * @return mixed
	 */
	public function resolve($abstract, array $arguments = array());
	
	/**
	 * Call a callable and attempt to resolve its parameters using services
	 * registered with the container.
	 * 
	 * @param callable $callable
	 * @param array    $arguments [optional]
	 * @return mixed
	 */
	public function call($callable, array $arguments = array());
	
}
