<?php
namespace Darya\Tests\Unit\View;

use Darya\View;
use PHPUnit_Framework_TestCase;

class ResolverTest extends PHPUnit_Framework_TestCase
{
	protected function phpResolver()
	{
		return new View\Resolver(View\Php::class, __DIR__ . '/Fixtures/views', ['php']);
	}

	public function testResolve()
	{
		$viewResolver = $this->phpResolver();

		$path = $viewResolver->resolve('hello');

		$this->assertSame(__DIR__ . '/Fixtures/views/hello.php', $path);
	}

	public function testCreate()
	{
		$viewResolver = $this->phpResolver();

		$view = $viewResolver->create('hello', ['hello' => 'hello there']);

		$this->assertInstanceOf(View\Php::class, $view);
		$this->assertSame('hello there', (string) $view);
	}
}
