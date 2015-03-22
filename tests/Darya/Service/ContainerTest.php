<?php
use Darya\Service\Container;

class ContainerTest extends PHPUnit_Framework_TestCase {
	
	protected function assertSomething(Something $something) {
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
	
	public function testRecursiveAlias() {
		$container = new Container(array(
			'Something'     => 'Something',
			'SomeInterface' => 'Something',
			'some'          => 'SomeInterface'
		));
		
		var_dump($container->some);
		$this->assertInstanceOf('Something', $container->some);
	}

}

interface SomeInterface {}

class Something implements SomeInterface {
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
