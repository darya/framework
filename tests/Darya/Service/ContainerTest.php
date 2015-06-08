<?php
use Darya\Service\Container;

class ContainerTest extends PHPUnit_Framework_TestCase {
	
	protected function assertSomething($something) {
		$this->assertInstanceOf('Something', $something);
		$this->assertInstanceOf('AnotherThing', $something->another);
		$this->assertInstanceOf('SomethingElse', $something->else);
		$this->assertInstanceOf('SomethingElse', $something->another->else);
	}
	
	public function testCreate() {
		$container = new Container;
		
		$something = $container->create('Something');
		
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
			'SomeInterface'  => 'Something',
			'OtherInterface' => 'SomeInterface',
			'some'           => 'SomeInterface',
			'second'         => 'some',
			'first'          => 'second',
			'other'          => 'OtherInterface'
		));
		
		$this->assertInstanceOf('SomeInterface', $container->some);
		$this->assertInstanceOf('SomeInterface', $container->first);
		$this->assertInstanceOf('SomeInterface', $container->second);
		$this->assertInstanceOf('OtherInterface', $container->other);
		
		$this->assertSomething($container->some);
		$this->assertSomething($container->first);
		$this->assertSomething($container->second);
		$this->assertSomething($container->other);
		
	}
	
	public function testExplicitRecursiveAliases() {
		$container = new Container(array(
			'Something'      => 'Something',
			'SomeInterface'  => 'Something',
			'OtherInterface' => 'Something'
		));
		
		$container->alias('some', 'SomeInterface');
		$container->alias('other', 'OtherInterface');
		
		$this->assertInstanceOf('SomeInterface', $container->some);
		$this->assertInstanceOf('OtherInterface', $container->other);
		
		$this->assertSomething($container->some);
		$this->assertSomething($container->other);
	}
}

interface SomeInterface {}

interface OtherInterface {}

class Something implements SomeInterface, OtherInterface {
	public $another;
	public $else;
	public function __construct(AnotherThing $another, SomethingElse $else) {
		$this->another = $another;
		$this->else = $else;
	}
}

class AnotherThing {
	public $else;
	public function __construct(SomethingElse $else) {
		$this->else = $else;
	}
}

class SomethingElse {}
