<?php
use Darya\Http\Cookies;

class CookiesTest extends PHPUnit_Framework_TestCase {
	
	public function testHas() {
		$cookies = new Cookies;
		
		$this->assertFalse($cookies->has('cookie'));
		
		$cookies->set('cookie', 'value', 'tomorrow');
		
		$this->assertTrue($cookies->has('cookie'));
	}
	
	public function testCookieExpiration() {
		$cookies = new Cookies;
		
		$timestamp = time();
		
		$cookies->set('cookie', 'value', $timestamp);
		
		$this->assertEquals($timestamp, $cookies->get('cookie', 'expire'));
		
		$cookies->set('cookie2', 'value', '1 day');
		
		$this->assertEquals(date('Ymd', strtotime('1 day')), date('Ymd', $cookies->get('cookie2', 'expire')));
	}
	
}
