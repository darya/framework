<?php
namespace Darya\Events;

/**
 * Darya's event dispatcher interface.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Dispatchable
{
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
