<?php
namespace Darya\Events;

/**
 * Event subscriber interface for Darya's event system.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Subscriber {
	
	/**
	 * Returns event-to-listener pairs that this object subscribes to.
	 * 
	 * Example:
	 *   array(
	 *     'event.name'  => array($this, 'listener'),
	 *     'other.event' => function($argument) { return $argument; }
	 *   );
	 * 
	 * @return array
	 */
	public function getEventSubscriptions();
	
}
