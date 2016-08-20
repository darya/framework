<?php
namespace Darya\Tests\Request;

use PHPUnit_Framework_TestCase;
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
	
	public function testPathConsistency() {
		$request = Request::create('/awesome/path');
		
		$this->assertEquals('/awesome/path', $request->path());
		
		$request = Request::create('http://swag.com/awesome/path');
		
		$this->assertEquals('/awesome/path', $request->path());
		
		$request = Request::create('http://swag.com/awesome/path?test=parameter');
		
		$this->assertEquals('/awesome/path', $request->path());
		
		$request = Request::create('//swag.com/awesome/path?test=parameter');
		
		$this->assertEquals('/awesome/path', $request->path());
		
		$request = Request::create('http://swag.com/awesome/');
		
		$this->assertEquals('/awesome/', $request->path());
		
		$request = Request::create('//swag.com/awesome/?test=parameter');
		
		$this->assertEquals('/awesome/', $request->path());
		
		$request = Request::create('http://swag.com/awesome');
		
		$this->assertEquals('/awesome', $request->path());
		
		$request = Request::create('http://swag.com/awesome?test=parameter');
		
		$this->assertEquals('/awesome', $request->path());
	}
	
	public function testCaseSensitivity() {
		$request = Request::create('test/request', 'GET', array(
			'get' => array(
				'woah' => 'test',
				'WOAH' => 'TEST',
				'Woah' => 'Test'
			),
			'post' => array(
				'holy' => 'crap',
				'HOLY' => 'CRAP',
				'Holy' => 'Crap'
			),
			'cookie' => array(
				'clam' => 'boob',
				'CLAM' => 'BOOB',
				'Clam' => 'Boob'
			),
			'file' => array(
				'dogs' => 'cats',
				'Dogs' => 'Cats',
				'DOGS' => 'CATS'
			),
			'server' => array(
				'blam' => 'BLAM',
				'Blam' => 'Blam'
			),
			'header' => array(
				'content-type' => 'text/html',
				'Content-type' => 'application/json'
			)
		));
		
		$this->assertEquals('test', $request->get('woah'));
		$this->assertEquals('TEST', $request->get('WOAH'));
		$this->assertEquals('Test', $request->get('Woah'));
		
		$this->assertEquals('crap', $request->post('holy'));
		$this->assertEquals('CRAP', $request->post('HOLY'));
		$this->assertEquals('Crap', $request->post('Holy'));
		
		$this->assertEquals('boob', $request->cookie('clam'));
		$this->assertEquals('BOOB', $request->cookie('CLAM'));
		$this->assertEquals('Boob', $request->cookie('Clam'));
		
		$this->assertEquals('cats', $request->file('dogs'));
		$this->assertEquals('CATS', $request->file('DOGS'));
		$this->assertEquals('Cats', $request->file('Dogs'));
		
		$this->assertEquals('Blam', $request->server('blam'));
		$this->assertEquals('Blam', $request->server('BLAM'));
		$this->assertEquals('Blam', $request->server('Blam'));
		
		$this->assertEquals('application/json', $request->header('content-type'));
		$this->assertEquals('application/json', $request->header('CONTENT-TYPE'));
		$this->assertEquals('application/json', $request->header('Content-type'));
	}
	
	public function testDefaultValues() {
		$request = Request::create('/test', 'GET', array(
			'get' => array(
				'one' => 1,
				'two' => 2,
				'three' => 3
			)
		));
		
		$this->assertEquals(1, $request->get('one', 2));
		$this->assertEquals(1, $request->get('nothing', 1));
		$this->assertEquals(2, $request->post('nothing', 2));
		$this->assertEquals(3, $request->cookie('nothing', 3));
		$this->assertEquals(4, $request->file('nothing', 4));
		$this->assertEquals(5, $request->server('nothing', 5));
		$this->assertEquals('application/json', $request->server('content-type', 'application/json'));
	}
	
}
