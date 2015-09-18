<?php
use Darya\Storage\InMemory;

class InMemoryTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * Provides test data for in-memory storage.
	 * 
	 * @return array
	 */
	protected function inMemoryData() {
		return array(
			'pages' => array(
				array(
					'id' => 1,
					'name' => 'My page',
					'text' => 'Page text'
				)
			),
			'users' => array(
				array(
					'id'   => 1,
					'name' => 'Chris'
				),
				array(
					'id'   => 2,
					'name' => 'Bethany'
				)
			)
		);
	}
	
	/**
	 * Retrieve the in-memory storage to test with.
	 * 
	 * @return InMemory
	 */
	protected function storage() {
		return new InMemory($this->inMemoryData());
	}
	
	public function testSimpleRead() {
		$data = $this->inMemoryData();
		
		$storage = new InMemory($data);
		
		$users = $storage->read('users');
		
		$this->assertEquals($data['users'], $users);
		
		$pages = $storage->read('pages');
		
		$this->assertEquals($data['pages'], $pages);
		
		$this->assertEquals(array(), $storage->read('non_existent'));
	}
	
	public function testSimpleListing() {
		$storage = $this->storage();
		
		$listing = $storage->listing('users', 'id');
		
		$this->assertEquals(array(array('id' => 1), array('id' => 2)), $listing);
	}
	
	public function testCount() {
		$data = $this->inMemoryData();
		
		$storage = new InMemory($data);
		
		$this->assertEquals(2, $storage->count('users'));
		$this->assertEquals(1, $storage->count('pages'));
		$this->assertEquals(0, $storage->count('non_existent'));
	}
	
}
