<?php
namespace Darya\Tests\Unit\Service;

use PHPUnit_Framework_TestCase;

use Darya\Service\Container;

use Darya\Tests\Unit\Service\Fixtures\AnotherThing;
use Darya\Tests\Unit\Service\Fixtures\OtherInterface;
use Darya\Tests\Unit\Service\Fixtures\SomeInterface;
use Darya\Tests\Unit\Service\Fixtures\Something;
use Darya\Tests\Unit\Service\Fixtures\SomethingElse;

class ContainerTest extends PHPUnit_Framework_TestCase {
	
	protected function assertSomething($something) {
		$this->assertInstanceOf(Something::class, $something);
		$this->assertInstanceOf(AnotherThing::class, $something->another);
		$this->assertInstanceOf(SomethingElse::class, $something->else);
		$this->assertInstanceOf(SomethingElse::class, $something->another->else);
	}
	
	public function testCreate() {
		$container = new Container;
		
		$something = $container->create(Something::class);
		
		$this->assertSomething($something);
	}
	
	public function testCall() {
		$container = new Container;
		
		$closure = function (Something $something) {
			return $something;
		};
		
		$something = $container->call($closure);
		
		$this->assertSomething($something);
	}
	
	public function testCallDefaultValue() {
		$container = new Container;
		
		$closure = function (Something $something, $value = 'default') {
			return array($something, $value);
		};
		
		list($something, $value) = $container->call($closure);
		
		$this->assertSomething($something);
		$this->assertEquals('default', $value);
	}
	
	public function testCallArgs() {
		$container = new Container;
		
		$closure = function ($one, $two = 'two', $three = 'three') {
			return func_get_args();
		};
		
		$result = $container->call($closure);
		
		$this->assertEquals(array(null, 'two', 'three'), $result);
		
		$result = $container->call($closure, array(
			'three', 2, 'one'
		));
		
		$this->assertEquals(array('three', 2, 'one'), $result);
		
		$result = $container->call($closure, array(
			'three' => 1,
			'two' => 2,
			'one' => 3
		));
		
		$this->assertEquals(array(3, 2, 1), $result);
	}
	
	public function testRecursiveServices() {
		$container = new Container(array(
			SomeInterface::class  => Something::class,
			OtherInterface::class => SomeInterface::class,
			'some'                => SomeInterface::class,
			'second'              => 'some',
			'first'               => 'second',
			'other'               => OtherInterface::class
		));
		
		$this->assertInstanceOf(SomeInterface::class, $container->some);
		$this->assertInstanceOf(SomeInterface::class, $container->first);
		$this->assertInstanceOf(SomeInterface::class, $container->second);
		$this->assertInstanceOf(OtherInterface::class, $container->other);
		
		$this->assertSomething($container->some);
		$this->assertSomething($container->first);
		$this->assertSomething($container->second);
		$this->assertSomething($container->other);
		
	}
	
	public function testExplicitRecursiveAliases() {
		$container = new Container(array(
			Something::class      => Something::class,
			SomeInterface::class  => Something::class,
			OtherInterface::class => Something::class
		));
		
		$container->alias('some', SomeInterface::class);
		$container->alias('other', OtherInterface::class);
		
		$this->assertInstanceOf(SomeInterface::class, $container->some);
		$this->assertInstanceOf(OtherInterface::class, $container->other);
		
		$this->assertSomething($container->some);
		$this->assertSomething($container->other);
	}
}
