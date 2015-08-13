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
		
		$translator->parameterise(false);
		
		$result = $translator->translate($query);
		$this->assertEquals($result->string(), "SELECT TOP 5 * FROM users WHERE age >= 23 AND name LIKE '%test%' ORDER BY id ASC");
		
		$query->fields(array('id', 'firstname', 'lastname'));
		$query->where('role_id', array(1, 2, '3', '4', 5));
		$query->limit(0);
		
		$translator->parameterise(true);
		
		$result = $translator->translate($query);
		$this->assertEquals($result->string(), "SELECT id, firstname, lastname FROM users WHERE age >= ? AND name LIKE ? AND role_id IN (?, ?, ?, ?, ?) ORDER BY id ASC");
		
		$translator->parameterise(false);
		
		$result = $translator->translate($query);
		$this->assertEquals($result->string(), "SELECT id, firstname, lastname FROM users WHERE age >= 23 AND name LIKE '%test%' AND role_id IN (1, 2, '3', '4', 5) ORDER BY id ASC");
	}
	
}