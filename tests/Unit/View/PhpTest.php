<?php
namespace Darya\Tests\Unit\View;

use PHPUnit_Framework_TestCase;
use Darya\View\Php;

class PhpTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		//
	}

	public function testSimpleRender()
	{
		$view = new Php(__DIR__ . '/Fixtures/views/hello.php', ['hello' => 'hello there']);

		$this->assertSame('hello there', $view->render());
		$this->assertSame('hello there', (string) $view);
	}
}
