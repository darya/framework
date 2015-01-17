<?php
namespace Darya\Events;

use Darya\Events\SubscriberInterface;

/**
 * Event subscription interface for Darya's event system.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface SubscribableInterface {
	
	/**
	 * Register the given subscriber's event listeners.
	 * 
	 * @param \Darya\Events\SubscriberInterface $subscriber
	 */
	public function subscribe(SubscriberInterface $subscriber);
	
	/**
	 * Unregister the given subscriber's event listeners.
	 * 
	 * @param \Darya\Events\SubscriberInterface $subscriber
	 */
	public function unsubscribe(SubscriberInterface $subscriber);
	
}
