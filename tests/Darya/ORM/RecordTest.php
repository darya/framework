<?php
use Darya\ORM\Record;
use Darya\Storage\InMemory;

class RecordTest extends PHPUnit_Framework_TestCase {
	
	protected $storage;
	
	protected function setUp() {
		static $data;
		
		if (!$data)
			$data = json_decode(file_get_contents(__DIR__  .'/data/cms.json'), true);
		
		$this->storage = new InMemory($data);
		
		Record::setSharedStorage($this->storage);
	}
	
	public function testFind() {
		$user = User::find(2);
		
		$this->assertEquals('Bethany', $user->firstname);
	}
	
}

class User extends Record {
	
	
	
}
