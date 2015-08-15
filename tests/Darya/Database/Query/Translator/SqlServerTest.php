<?php
use Darya\Storage\Query;
use Darya\Database\Query\Translator\SqlServer;

class SqlServerTest extends PHPUnit_Framework_TestCase {
	
	public function testSelect() {
		$translator = new SqlServer;
		
		$query = new Query('users');
		$query->where('age >=', 23)
			->where('name like', '%test%')
			->order('id')
			->limit(5);
		
		$result = $translator->translate($query);
		$this->assertEquals($result->string(), "SELECT TOP 5 * FROM users WHERE age >= ? AND name LIKE ? ORDER BY id ASC");
		$this->assertEquals($result->parameters(), array(23, '%test%'));
		
		$query->fields(array('id', 'firstname', 'lastname'));
		$query->where('role_id', array(1, 2, '3', '4', 5));
		$query->limit(0);
		
		$result = $translator->translate($query);
		$this->assertEquals($result->string(), "SELECT id, firstname, lastname FROM users WHERE age >= ? AND name LIKE ? AND role_id IN (?, ?, ?, ?, ?) ORDER BY id ASC");
		$this->assertEquals($result->parameters(), array(23, '%test%', 1, 2, '3', '4', 5));
	}
	
	public function testUpdate() {
		$translator = new SqlServer;
		
		$query = new Query('users');
		$query->update(array(
				'age' => 24,
				'comment' => "Oh god I'm too old"
			))->where('age >=', 23)
			->where('name like', '%swag%');
		
		$result = $translator->translate($query);
		$this->assertEquals($result->string(), "UPDATE users SET age = ?, comment = ? WHERE age >= ? AND name LIKE ?");
		$this->assertEquals($result->parameters(), array(24, "Oh god I'm too old", 23, '%swag%'));
		
		$query->limit(3);
		
		$result = $translator->translate($query);
		$this->assertEquals($result->string(), "UPDATE TOP 3 users SET age = ?, comment = ? WHERE age >= ? AND name LIKE ?");
		$this->assertEquals($result->parameters(), array(24, "Oh god I'm too old", 23, '%swag%'));
	}
	
}