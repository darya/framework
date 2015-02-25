<?php
namespace Darya\Events;

/**
 * Event listener manager interface for Darya's event system.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface ListenableInterface {
	
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
	
}
