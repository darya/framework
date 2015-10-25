<?php
use Darya\ORM\Record;
use Darya\Storage\InMemory;

class RecordTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * The in-memory storage used for testing Darya's active record ORM.
	 * 
	 * @var InMemory
	 */
	protected static $storage;
	
	public static function setUpBeforeClass() {
		$file = __DIR__  . '/data/cms.json';
		$data = json_decode(file_get_contents($file), true);
		
		static::$storage = new InMemory($data);
		
		Record::setSharedStorage(static::$storage);
	}
	
	public function testFind() {
		$user = User::find(2);
		
		$this->assertEquals('Bethany', $user->firstname);
	}
	
	public function testAll() {
		$users = User::all();
		
		$this->assertEquals(3, count($users));
		
		$this->assertEquals('Chris', $users[0]->firstname);
		$this->assertEquals('Bethany', $users[1]->firstname);
		
		$users = User::all(['firstname !=' => 'john']);
		
		$this->assertEquals(2, count($users));
		
		// TODO: Test sorting and limiting
	}
	
	public function testManyToMany() {
		$user = User::find(1);
		
		$roles = $user->roles;
		
		$this->assertEquals(2, count($roles));
		
		$role = Role::find(2);
		
		$users = $role->users;
		
		$this->assertEquals(1, count($users));
		$this->assertEquals('Bethany', $users[0]->firstname);
	}
	
}

class User extends Record {
	
	protected $relations = array(
		'roles' => ['belongs_to_many', 'Role', null, null, 'user_roles']
	);
	
}

class Role extends Record {
	
	protected $relations = array(
		'users' => ['belongs_to_many', 'User', null, null, 'user_roles']
	);
	
}
