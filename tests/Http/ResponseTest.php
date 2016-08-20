<?php
namespace Darya\Tests\Http;

use PHPUnit_Framework_TestCase;
use Darya\Http\Response;

class ResponseTest extends PHPUnit_Framework_TestCase
{
	public function testStatus()
	{
		$response = new Response;
		
		$response->status(404);
		$this->assertEquals(404, $response->status());
		
		$response->status('404');
		$this->assertEquals(404, $response->status());
	}
	
	public function testHeaders()
	{
		$response = new Response;
		$response->header('Test: Value');
		$this->assertEquals(array('Test' => 'Value'), $response->headers());
		
		$response = new Response;
		$response->header('Swag');
		$this->assertEquals(array('Swag' => null), $response->headers());
		
		$response = new Response;
		$response->headers(array('Test: Value', 'Swag: Swish'));
		$this->assertEquals(array('Test' => 'Value', 'Swag' => 'Swish'), $response->headers());
	}
	
	public function testContent()
	{
		$response = new Response;
		
		$response->content('test');
		$this->assertEquals('test', $response->content());
		
		$content = new ResponseContent;
		$response->content($content);
		$this->assertEquals($content, $response->content());
		$this->assertEquals('darya', $response->body());
	}
	
	public function testJsonContent()
	{
		$response = new Response;
		
		$array = array(
			'test' => 'value',
			'swag' => 'swish'
		);
		
		$response->content($array);
		
		$this->assertEquals($array, $response->content());
		$this->assertEquals('{"test":"value","swag":"swish"}', $response->body());
	}
	
	public function testDynamicProperties()
	{
		$response = new Response('content', array(
			'Test-Header: Value'
		));
		
		$response->redirect('http://darya.io/');
		
		$this->assertEquals(200, $response->status);
		$this->assertEquals('content', $response->content);
		$this->assertEquals(array('Test-Header' => 'Value', 'Location' => 'http://darya.io/'), $response->headers);
		$this->assertEquals(true, $response->redirected);
		$this->assertInstanceOf('Darya\Http\Cookies', $response->cookies);
	}
}

class ResponseContent
{
	public function __toString()
	{
		return 'darya';
	}
}
