<?php
namespace Darya\Events;

/**
 * Event subscriber interface for Darya's event dispatcher system.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface SubscriberInterface {
	
	/**
	 * Returns event/listener pairings that this object subscribes to.
	 * 
	 * Example:
	 *   array(
	 *     'event.name'  => array($this, 'listener'),
	 *     'other.event' => function($argument){return $argument;}
	 *   );
	 * 
	 * @return array
	 */
	public function getEventSubscriptions();
	
}
