<?php
namespace Darya\Tests\Unit\Foundation;

use PHPUnit_Framework_TestCase;
use Darya\Foundation\Configuration;

class ConfigurationTest extends PHPUnit_Framework_TestCase {
	
	protected function data() {
		return array(
			'aliases' => array(
				'config'   => 'Darya\Foundation\Configuration',
				'database' => 'Darya\Database\Connection',
				'event'    => 'Darya\Events\Contracts\Dispatcher',
				'request'  => 'Darya\Http\Request',
				'response' => 'Darya\Http\Response',
				'router'   => 'Darya\Routing\Router',
				'session'  => 'Darya\Http\Session',
				'storage'  => 'Darya\Storage\Readable',
				'view'     => 'Darya\View\Resolver'
			),
			'base_url' => '',
			'debug'    => false,
			'project'  => array(
				'namespace' => 'Application',
				'base_path' => '/my/base/path'
			),
			'database' => array(
				'type'     => 'mysql',
				'hostname' => 'localhost',
				'username' => 'root',
				'password' => 'password',
				'database' => 'database'
			)
		);
	}
	
	protected function config() {
		$data = $this->data();
		
		return new Configuration\InMemory($data);
	}
	
	public function testHas() {
		$config = $this->config();
		
		$this->assertTrue($config->has('database'));
		$this->assertTrue($config->has('database.type'));
		$this->assertFalse($config->has('database.undefined'));
		$this->assertTrue($config->has('debug'));
	}
	
	public function testGet() {
		$data = $this->data();
		$config = $this->config();
		
		$this->assertEquals($data['aliases'], $config->get('aliases'));
		$this->assertEquals('Darya\Foundation\Configuration', $config->get('aliases.config'));
		
		$this->assertEquals('Application', $config->get('project.namespace'));
		$this->assertEquals('localhost', $config->get('database.hostname'));
	}
	
	public function testSet() {
		$config = $this->config();
		
		$this->assertEquals('password', $config->get('database.password'));
		$config->set('database.password', 'swagger');
		$this->assertEquals('swagger', $config->get('database.password'));
	}
	
	public function testArrayAccess() {
		$config = $this->config();
		
		$this->assertTrue(isset($config['project.namespace']));
		$this->assertFalse(isset($config['project.undefined']));
		
		$this->assertEquals('password', $config['database.password']);
		$config['database.password'] = 'swagger';
		$this->assertEquals('swagger', $config['database.password']);
		
		unset($config['database.password']);
		
		$this->assertNull($config['database.password']);
	}
	
}
