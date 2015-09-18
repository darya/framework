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
	
	public function testSimpleRead() {
		$data = $this->inMemoryData();
		
		$storage = new InMemory($data);
		
		$users = $storage->read('users');
		
		$this->assertEquals($data['users'], $users);
		
		$pages = $storage->read('pages');
		
		$this->assertEquals($data['pages'], $pages);
	}
	
}
