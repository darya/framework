<?php
namespace Darya\Events;

use Darya\Events\DispatcherInterface;

/**
 * Darya's event dispatcher implementation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Dispatcher implements DispatcherInterface {
	
	/**
	 * Keys are event names and values are callables.
	 * 
	 * @var array
	 */
	protected $listeners;
	
	/**
	 * Register a listener with the given event.
	 * 
	 * @param string   $event
	 * @param callable $callable
	 * @return bool
	 */
	public function listen($event, $callable) {
		$this->listeners[$event][] = $callable;
	}
	
	/**
	 * Dispatch the given event.
	 * 
	 * Optionally accepts arguments to pass to the event's registered listeners.
	 * 
	 * @param string $event
	 * @param array  $arguments
	 * @return mixed
	 */
	public function dispatch($event, array $arguments) {
		$results = array();
		
		foreach ((array) $this->listeners[$event] as $listener) {
			if (is_callable($listener)) {
				$results[] = call_user_func_array($listener, $arguments);
			}
		}
		
		return $results ?: null;
	}
	
}
