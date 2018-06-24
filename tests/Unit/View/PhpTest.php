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

		$result = $view->render();

		$this->assertSame('hello there', $result);
	}
}
