<?php
namespace Darya\Tests\Events;

use PHPUnit_Framework_TestCase;
use Darya\Events\Dispatcher;

class DispatcherTest extends PHPUnit_Framework_TestCase
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
}
