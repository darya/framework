# `Darya\Events`

Darya's events package provides a simple implementation of the observer pattern.

## Usage

- [Listeners and dispatchers](#listeners-and-dispatchers)
- [Subscribers](#subscribers)

### Listeners and dispatchers

The `Dispatcher` class is Darya's implementation of the `Dispatchable`
interface.

```php
use Darya\Events\Dispatcher;

$dispatcher = new Dispatcher;
```

Listening to events is as simple as providing an event name and any PHP callable
to the `listen()` method.

```php
$dispatcher->listen('some_event', function ($thing) {
	return "one $thing";
});

$dispatcher->listen('some_event', function ($thing) {
	return "two $thing" . 's';
});
```

Then, to fire off an event we can use the `dispatch()` method with an event
name and an array of arguments to pass to each listener.

The result of this call will be an array of return values from each listener.

```php
$results = $dispatcher->dispatch('some_event', array('thing')); // array('one thing', 'two things')
```

To detach a listener, use `unlisten()`.

```php
$dispatcher->unlisten('event', $listener);
```


### Subscribers

Subscribers are objects that listen to multiple events.

They need to implement a public `getEventSubscriptions()` method from the
`Subscriber` interface, which should return an array with event names for
keys and corresponding listeners for values.

```php
use Darya\Events\Subscriber;

class EventSubscriber implements Subscriber
{
	/**
	 * Retrieve the subscriptions.
	 * 
	 * @return array
	 */
	public function getEventSubscriptions()
	{
		return array(
			'event.name'  => array($this, 'listener'),
			'other.event' => function ($argument) {
				return $argument . ' is awesome';
			}
		);
	}
	
	/**
	 * Increment the given value.
	 *
	 * @param mixed $value
	 * @return value
	 */
	public function listener($value)
	{
		return ++$value;
	}
}
```

We can then give a dispatcher an instance of the subscriber using the
`subscribe()` method.

```php
$subscriber = new EventSubscriber;

$dispatcher->subscribe($susbcriber);
```

Each listener from the subscriber will then respond when an event is dispatched.

```php
$dispatcher->dispatch('event.name', array(1)); // array(2)
$dispatcher->dispatch('other.event', array('Darya')); // array('Darya is awesome')
```

And to unsubscribe, just call `unsubscribe()` with the same reference to the
subscriber instance, as you would with single listeners.

```php
$dispatcher->unsubscribe($subscriber);
```
