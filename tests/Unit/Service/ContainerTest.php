<?php

namespace Darya\Tests\Unit\Service;

use Darya\Service\Exceptions\ContainerException;
use Darya\Service\Exceptions\NotFoundException;
use PHPUnit_Framework_TestCase;

use Darya\Service\Container;

use Darya\Tests\Unit\Service\Fixtures\AnotherThing;
use Darya\Tests\Unit\Service\Fixtures\OtherInterface;
use Darya\Tests\Unit\Service\Fixtures\SomeInterface;
use Darya\Tests\Unit\Service\Fixtures\Something;
use Darya\Tests\Unit\Service\Fixtures\SomethingElse;

class ContainerTest extends PHPUnit_Framework_TestCase
{
	protected function assertSomething($something)
	{
		$this->assertInstanceOf(Something::class, $something);
		$this->assertInstanceOf(AnotherThing::class, $something->another);
		$this->assertInstanceOf(SomethingElse::class, $something->else);
		$this->assertInstanceOf(SomethingElse::class, $something->another->else);
	}

	public function testServiceNotFound()
	{
		$container = new Container;

		$this->setExpectedException(NotFoundException::class);

		$container->get('Foo');
	}

	public function testCreate()
	{
		$container = new Container;

		$something = $container->create(Something::class);

		$this->assertSomething($something);
	}

	public function testCall()
	{
		$container = new Container;

		$closure = function (Something $something) {
			return $something;
		};

		$something = $container->call($closure);

		$this->assertSomething($something);
	}

	public function testCallDefaultValue()
	{
		$container = new Container;

		$closure = function (Something $something, $value = 'default') {
			return [$something, $value];
		};

		list($something, $value) = $container->call($closure);

		$this->assertSomething($something);
		$this->assertEquals('default', $value);
	}

	public function testCallArgs()
	{
		$container = new Container;

		$closure = function ($one, $two = 'two', $three = 'three') {
			return func_get_args();
		};

		$this->setExpectedException(ContainerException::class);
		$container->call($closure);

		$result = $container->call($closure, [
			'three', 2, 'one'
		]);

		$this->assertEquals(['three', 2, 'one'], $result);

		$result = $container->call($closure, [
			'three' => 1,
			'two'   => 2,
			'one'   => 3
		]);

		$this->assertEquals([3, 2, 1], $result);
	}

	public function testRecursiveServices()
	{
		$container = new Container([
			SomeInterface::class  => Something::class,
			OtherInterface::class => SomeInterface::class,
			'some'                => SomeInterface::class,
			'second'              => 'some',
			'first'               => 'second',
			'other'               => OtherInterface::class
		]);

		$this->assertInstanceOf(SomeInterface::class, $container->some);
		$this->assertInstanceOf(SomeInterface::class, $container->first);
		$this->assertInstanceOf(SomeInterface::class, $container->second);
		$this->assertInstanceOf(OtherInterface::class, $container->other);

		$this->assertSomething($container->some);
		$this->assertSomething($container->first);
		$this->assertSomething($container->second);
		$this->assertSomething($container->other);
	}

	public function testExplicitRecursiveAliases()
	{
		$container = new Container([
			Something::class      => Something::class,
			SomeInterface::class  => Something::class,
			OtherInterface::class => Something::class
		]);

		$container->alias('some', SomeInterface::class);
		$container->alias('other', OtherInterface::class);

		$this->assertInstanceOf(SomeInterface::class, $container->some);
		$this->assertInstanceOf(OtherInterface::class, $container->other);

		$this->assertSomething($container->some);
		$this->assertSomething($container->other);
	}

	public function testDelegateContainer()
	{
		$container = new Container([
			SomeInterface::class => Something::class
		]);

		$container->alias('some', SomeInterface::class);

		// Delegate a second container
		$delegate = new Container([
			OtherInterface::class => Something::class
		]);

		$delegate->alias('other', OtherInterface::class);

		$container->delegate($delegate);

		// Resolve both dependencies from the parent container
		$this->assertInstanceOf(SomeInterface::class, $container->some);
		$this->assertInstanceOf(OtherInterface::class, $container->other);
		$this->assertInstanceOf(SomeInterface::class, $container->get(SomeInterface::class));
		$this->assertInstanceOf(OtherInterface::class, $container->get(OtherInterface::class));

		$this->assertSomething($container->some);
		$this->assertSomething($container->other);
		$this->assertSomething($container->get(SomeInterface::class));
		$this->assertSomething($container->get(OtherInterface::class));

		$this->setExpectedException(NotFoundException::class);
		$delegate->some;

		$this->setExpectedException(NotFoundException::class);
		$delegate->get(SomeInterface::class);
	}
}
