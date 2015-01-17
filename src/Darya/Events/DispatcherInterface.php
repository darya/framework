<?php
namespace Darya\Events;

/**
 * Darya's event dispatcher interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface DispatcherInterface {
	
	/**
	 * Register a listener with the given event.
	 * 
	 * @param string   $event
	 * @param callable $callable
	 */
	public function listen($event, $callable);
	
	/**
	 * Unregister a listener from the given event.
	 * 
	 * @param string   $event
	 * @param callable $callable
	 */
	public function unlisten($event, $callable);
	
	/**
	 * Dispatch the given event, invoking all its listeners.
	 * 
	 * Optionally accepts arguments to pass to the event's registered listeners.
	 * 
	 * @param string $event
	 * @param array  $arguments
	 * @return mixed
	 */
	public function dispatch($event, array $arguments);
	
}
