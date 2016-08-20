<?php
use Darya\Database\Factory;

class FactoryTest extends PHPUnit_Framework_TestCase {
	
	protected function factory() {
		return new Factory('mysql');
	}
	
	protected function options() {
		return array(
			'hostname' => 'localhost',
			'username' => 'root',
			'password' => 'password',
			'database' => 'darya'
		);
	}
	
	public function testCreate() {
		$factory = $this->factory();
		
		$connection = $factory->create('mysql', $this->options());
		
		$this->assertInstanceOf('Darya\Database\Connection\MySql', $connection);
		
		$connection = $factory->create('mssql', $this->options());
		
		$this->assertInstanceOf('Darya\Database\Connection\SqlServer', $connection);
		
		// Test falling back to the default
		$connection = $factory->create(null, $this->options());
		
		$this->assertInstanceOf('Darya\Database\Connection\MySql', $connection);
	}
	
	public function testCreateException() {
		$this->setExpectedException('UnexpectedValueException');
		
		$factory = $this->factory();
		
		$factory->create('undefined');
	}
	
}
