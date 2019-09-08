<?php
namespace Darya\Tests\Unit\View;

use Darya\View;
use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
	protected function resolver($engine = View\Php::class)
	{
		return new View\Resolver($engine, __DIR__ . '/Fixtures/views', ['php']);
	}

	/**
	 * Test resolving view paths.
	 */
	public function testResolveViewPath()
	{
		$viewResolver = $this->resolver();

		$path = $viewResolver->resolve('hello');

		$this->assertSame(__DIR__ . '/Fixtures/views/hello.php', $path);
	}

	/**
	 * Test creating new views.
	 */
	public function testCreateView()
	{
		$viewResolver = $this->resolver();

		$view = $viewResolver->create('hello', ['hello' => 'hello there']);

		$this->assertInstanceOf(View\Php::class, $view);
		$this->assertSame('hello there', $view->render());
	}
}
