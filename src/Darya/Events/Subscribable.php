<?php
namespace Darya\Events;

/**
 * Event subscription interface for Darya's event system.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Subscribable
{
	/**
	 * Register the given subscriber's event listeners.
	 *
	 * @param Subscriber $subscriber
	 */
	public function subscribe(Subscriber $subscriber);

	/**
	 * Unregister the given subscriber's event listeners.
	 *
	 * @param Subscriber $subscriber
	 */
	public function unsubscribe(Subscriber $subscriber);
}
