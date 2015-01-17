<?php
namespace Darya\Events;

/**
 * Darya's event dispatchable interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface DispatchableInterface {
	
	/**
	 * Dispatch the given event.
	 * 
	 * Optionally accepts arguments to pass to the event's registered listeners.
	 * 
	 * @param string $event
	 * @param array  $arguments [optional]
	 * @return mixed
	 */
	public function dispatch($event, array $arguments = array());
	
}
