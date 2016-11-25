<?php
namespace Darya\Tests\Unit\Database\Query\Translator;

use PHPUnit_Framework_TestCase;
use Darya\Database\Storage\Query;
use Darya\Database\Connection;
use Darya\Database\Query\Translator;

class SqlServerTest extends PHPUnit_Framework_TestCase {
	
	protected function translator() {
		return new Translator\SqlServer;
	}
	
	public function testSelect() {
		$translator = $this->translator();
		
		$query = new Query('users');
		$query->where('age >=', 23)
			->where('name like', '%test%')
			->order('id')
			->limit(5);
		
		$result = $translator->translate($query);
		$this->assertEquals("SELECT TOP 5 * FROM users WHERE age >= ? AND name LIKE ? ORDER BY id ASC", $result->string);
		$this->assertEquals(array(23, '%test%'), $result->parameters);
		
		$query->fields(array('id', 'firstname', 'lastname'));
		$query->where('role_id', array(1, 2, '3', '4', 5));
		$query->limit(0);
		
		$result = $translator->translate($query);
		$this->assertEquals("SELECT id, firstname, lastname FROM users WHERE age >= ? AND name LIKE ? AND role_id IN (?, ?, ?, ?, ?) ORDER BY id ASC", $result->string);
		$this->assertEquals(array(23, '%test%', 1, 2, '3', '4', 5), $result->parameters);
		
		$query->group('firstname');
		$query->having('id >', 6);
		$query->having('id <', 7);
		
		$result = $translator->translate($query);
		$this->assertEquals("SELECT id, firstname, lastname FROM users WHERE age >= ? AND name LIKE ? AND role_id IN (?, ?, ?, ?, ?) GROUP BY firstname HAVING id > ? AND id < ? ORDER BY id ASC", $result->string);
		$this->assertEquals(array(23, '%test%', 1, 2, '3', '4', 5, 6, 7), $result->parameters);
	}
	
	public function testInsert() {
		$translator = $this->translator();
		
		$query = new Query('users');
		$query->create(array(
			'firstname' => 'Chris',
			'lastname'  => 'Andrew',
			'age'       => 23,
			'role_id'   => 1
		));
		
		$result = $translator->translate($query);
		$this->assertEquals("INSERT INTO users (firstname, lastname, age, role_id) VALUES (?, ?, ?, ?)", $result->string);
		$this->assertEquals(array('Chris', 'Andrew', 23, 1), $result->parameters);
	}
	
	public function testUpdate() {
		$translator = $this->translator();
		
		$query = new Query('users');
		$query->update(array(
				'age' => 24,
				'comment' => "Oh god I'm too old"
			))->where('age >=', 23)
			->where('name like', '%swag%');
		
		$result = $translator->translate($query);
		$this->assertEquals("UPDATE users SET age = ?, comment = ? WHERE age >= ? AND name LIKE ?", $result->string);
		$this->assertEquals(array(24, "Oh god I'm too old", 23, '%swag%'), $result->parameters);
		
		$query->where('role_id', array(1, 2, '3', '4', 5));
		$query->limit(3);
		
		$result = $translator->translate($query);
		$this->assertEquals("UPDATE TOP 3 users SET age = ?, comment = ? WHERE age >= ? AND name LIKE ? AND role_id IN (?, ?, ?, ?, ?)", $result->string);
		$this->assertEquals(array(24, "Oh god I'm too old", 23, '%swag%', 1, 2, '3', '4', 5), $result->parameters);
	}
	
	public function testDelete() {
		$translator = $this->translator();
		
		$query = new Query('users');
		$query->delete()
			->where('age <', 23)
			->where('type !=', 'normal');
		
		$result = $translator->translate($query);
		$this->assertEquals('DELETE FROM users WHERE age < ? AND type != ?', $result->string);
		$this->assertEquals(array(23, 'normal'), $result->parameters);
		
		$query->where('role_id not in', array(1, '2'));
		$query->limit('10');
		
		$result = $translator->translate($query);
		$this->assertEquals('DELETE TOP 10 FROM users WHERE age < ? AND type != ? AND role_id NOT IN (?, ?)', $result->string);
		$this->assertEquals(array(23, 'normal', 1, '2'), $result->parameters);
	}
	
	public function testNullParameters() {
		$translator = $this->translator();
		
		$query = new Query('users');
		$query->where('age', null);
		$query->where('role_id !=', null);
		
		$result = $translator->translate($query);
		$this->assertEquals('SELECT * FROM users WHERE age IS NULL AND role_id IS NOT NULL', $result->string);
		$this->assertEquals(array(), $result->parameters);
		
		$query = new Query('users');
		$query->update(array('age' => null));
		
		$result = $translator->translate($query);
		$this->assertEquals('UPDATE users SET age = NULL', $result->string);
		$this->assertSame(array(), $result->parameters);
		
		$query = new Query('users');
		$query->create(array(
			'name' => 'swag',
			'age'  => null
		));
		
		$result = $translator->translate($query);
		$this->assertEquals('INSERT INTO users (name, age) VALUES (?, NULL)', $result->string);
		$this->assertEquals(array('swag'), $result->parameters);
	}
	
	public function testArrayParameters() {
		$translator = $this->translator();
		
		$query = new Query('users');
		$query->where('role_id', array(1, 2, 3));
		$query->where('age !=', array(4, 5, 6));
		
		$result = $translator->translate($query);
		$this->assertEquals('SELECT * FROM users WHERE role_id IN (?, ?, ?) AND age NOT IN (?, ?, ?)', $result->string);
		$this->assertEquals(array(1, 2, 3, 4, 5, 6), $result->parameters);
	}
	
}