<?php
namespace Darya\Tests\Storage;

use PHPUnit_Framework_TestCase;
use Darya\Storage\Error;

class ErrorTest extends PHPUnit_Framework_TestCase {
	
	public function testError() {
		$error = new Error(1007, "Can't created database 'swag'; database exists");
		
		$this->assertEquals(1007, $error->number);
		$this->assertEquals("Can't created database 'swag'; database exists", $error->message);
	}
	
}