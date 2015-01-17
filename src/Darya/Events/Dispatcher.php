<?php
namespace Darya\Events;

use Darya\Events\DispatcherInterface;
use Darya\Events\SubscriberInterface;

/**
 * Darya's event dispatcher implementation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Dispatcher implements DispatcherInterface {
	
	/**
	 * Keys are event names and values are callables.
	 * 
	 * @var array
	 */
	protected $listeners;
	
	/**
	 * Ensure that the listeners index for the given event exists.
	 * 
	 * @param string $event
	 */
	private function touch($event) {
		if (!isset($this->listeners[$event])) {
			$this->listeners[$event] = array();
		}
	}
	
	/**
	 * Register a listener with the given event.
	 * 
	 * @param string   $event
	 * @param callable $callable
	 * @return void
	 */
	public function listen($event, $callable) {
		$this->touch($event);
		$this->listeners[$event][] = $callable;
	}
	
	/**
	 * Unregister a listener from the given event.
	 * 
	 * @param string   $event
	 * @param callable $callable
	 */
	public function unlisten($event, $callable) {
		$this->touch($event);
		$this->listeners[$event] = array_filter($this->listeners[$event], function ($value) use ($callable) {
			return $value !== $callable;
		});
	}
	
	/**
	 * Register the given subscriber's event listeners.
	 * 
	 * @param \Darya\Events\SubscriberInterface $subscriber
	 */
	public function subscribe(SubscriberInterface $subscriber) {
		$subscriptions = $subscriber->getEventSubscriptions();
		
		foreach ($subscriptions as $event => $listener) {
			$this->listen($event, $listener);
		}
	}
	
	/**
	 * Unregister the given subscriber's event listeners.
	 * 
	 * @param \Darya\Events\SubscriberInterface $subscriber
	 */
	public function unsubscribe(SubscriberInterface $subscriber) {
		$subscriptions = $subscriber->getEventSubscriptions();
		
		foreach ($subscriptions as $event => $listener) {
			$this->unlisten($event, $listener);
		}
	}
	
	/**
	 * Dispatch the given event.
	 * 
	 * Optionally accepts arguments to pass to the event's registered listeners.
	 * 
	 * @param string $event
	 * @param array  $arguments [optional]
	 * @return mixed
	 */
	public function dispatch($event, array $arguments = array()) {
		$this->touch($event);
		$results = array();
		
		foreach ((array) $this->listeners[$event] as $listener) {
			if (is_callable($listener)) {
				$results[] = call_user_func_array($listener, $arguments);
			}
		}
		
		return $results ?: null;
	}
	
}
