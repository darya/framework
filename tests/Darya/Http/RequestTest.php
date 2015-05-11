<?php
use Darya\Http\Request;

class RequestTest extends PHPUnit_Framework_TestCase {
	
	public function testUriPrecedence() {
		$request = Request::create('http://swag.com/awesome/path?super=querystring&swag=swagger', 'GET', array(
			'get' => array(
				'super' => 'some_nonsense_parameter',
				'swag'  => 'some_nonsense_parameter'
			),
			'server' => array(
				'REQUEST_URI' => 'some_nonsense_uri',
				'PATH_INFO' => 'some_nonsense_path'
			)
		));
		
		$this->assertEquals('swag.com', $request->host());
		$this->assertEquals('/awesome/path', $request->path());
		$this->assertEquals('/awesome/path?super=querystring&swag=swagger', $request->uri());
		$this->assertEquals('querystring', $request->get('super'));
		$this->assertEquals('swagger', $request->get('swag'));
	}
	
	/*public function testCaseSensitiveData() {
		
	}*/
	
}
