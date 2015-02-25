<?php
namespace Darya\Events;

/**
 * Event subscriber manager interface for Darya's event system.
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
