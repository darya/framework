<?php
namespace Darya\Events;

/**
 * Implemented by classes that can make use of an event dispatcher.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface DispatcherAware
{
	/**
	 * Set the event dispatcher.
	 *
	 * @param Dispatchable $dispatcher
	 * @return void
	 */
	public function setEventDispatcher(Dispatchable $dispatcher);
}
