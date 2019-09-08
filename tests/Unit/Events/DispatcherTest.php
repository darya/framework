<?php
namespace Darya\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use Darya\Events\Dispatcher;
use Darya\Tests\Unit\Events\Fixtures\SimpleSubscriber;

class DispatcherTest extends TestCase
{
	public function testListeners()
	{
		$dispatcher = new Dispatcher;

		$count = 0;

		// Test with a single listener
		$firstClosure = function ($number) use (&$count) {
			$count += $number;
		};

		$dispatcher->listen('some.event', $firstClosure);

		$dispatcher->dispatch('some.event', array(1));

		$this->assertEquals(1, $count);

		// Test with two listeners
		$secondClosure = function ($number) use (&$count) {
			$count += $number * 2;
		};

		$dispatcher->listen('some.event', $secondClosure);

		$dispatcher->dispatch('some.event', array(2));

		$this->assertEquals(7, $count);

		// Test after removing the first listener
		$dispatcher->unlisten('some.event', $firstClosure);

		$dispatcher->dispatch('some.event', array(1));

		$this->assertEquals(9, $count);
	}

	public function testSubscribers()
	{
		$dispatcher = new Dispatcher;

		// Test with a single subscriber
		$firstSubscriber = new SimpleSubscriber;

		$dispatcher->subscribe($firstSubscriber);

		$dispatcher->dispatch('some.event', array(1));

		$this->assertEquals(1, $firstSubscriber->number);

		// Test with two subscribers
		$secondSubscriber = new SimpleSubscriber;

		$dispatcher->subscribe($secondSubscriber);

		$dispatcher->dispatch('some.event', array(1));

		$this->assertEquals(2, $firstSubscriber->number);
		$this->assertEquals(1, $secondSubscriber->number);

		// Test after removing the first subscriber
		$dispatcher->unsubscribe($firstSubscriber);

		$dispatcher->dispatch('some.event', array(1));

		$this->assertEquals(2, $secondSubscriber->number);
	}
}
