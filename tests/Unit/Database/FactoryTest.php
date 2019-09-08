<?php
namespace Darya\Tests\Unit\Database\Query;

use PHPUnit\Framework\TestCase;
use Darya\Database\Factory;

class FactoryTest extends TestCase {

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
		$this->expectException('UnexpectedValueException');

		$factory = $this->factory();

		$factory->create('undefined');
	}

}
