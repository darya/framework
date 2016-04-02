<?php
namespace Darya\Events;

/**
 * Event subscriber interface for Darya's event system.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Subscriber
{
	/**
	 * Retrieve the subscriptions.
	 * 
	 * Example:
	 *   return array(
	 *     'event.name'  => array($this, 'listener'),
	 *     'other.event' => function ($argument) {
	 *       return $argument;
	 *     }
	 *   );
	 * 
	 * @return array
	 */
	public function getEventSubscriptions();
}
