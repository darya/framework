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
			'roles' => array(
				array(
					'id'   => 1,
					'name' => 'User'
				),
				array(
					'id'   => 2,
					'name' => 'Moderator'
				),
				array(
					'id'   => 3,
					'name' => 'Administrator'
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
		
		$this->assertEquals(3, $storage->count('roles'));
		$this->assertEquals(2, $storage->count('users'));
		$this->assertEquals(1, $storage->count('pages'));
		$this->assertEquals(0, $storage->count('non_existent'));
	}
	
	public function testCreate() {
		$storage = $this->storage();
		
		$storage->create('users', array('id' => 3, 'name' => 'b0ss'));
		
		$expected = array(
			array('id' => 1, 'name' => 'Chris'),
			array('id' => 2, 'name' => 'Bethany'),
			array('id' => 3, 'name' => 'b0ss')
		);
		
		$this->assertEquals($expected, $storage->read('users'));
	}
	
	public function testUpdate() {
		$storage = $this->storage();
		
		$affected = $storage->update('users', array('name' => 'BETHANY'), array('name like' => 'beth%'));
		
		$expected = array(
			array('id' => 1, 'name' => 'Chris'),
			array('id' => 2, 'name' => 'BETHANY')
		);
		
		$this->assertEquals(1, $affected);
		$this->assertEquals($expected, $storage->read('users'));
		
		$affected = $storage->update('users', array('test' => 'value'));
		
		$expected = array(
			array('id' => 1, 'name' => 'Chris', 'test' => 'value'),
			array('id' => 2, 'name' => 'BETHANY', 'test' => 'value')
		);
		
		$this->assertEquals(2, $affected);
		$this->assertEquals($expected, $storage->read('users'));
	}
	
	public function testEqualsFilter() {
		$storage = $this->storage();
		
		$users = $storage->read('users', array('name' => 'chris'));
		
		$this->assertEquals(array(array('id' => 1, 'name' => 'Chris')), $users);
		
		$users = $storage->read('users', array('id' => 2));
		
		$this->assertEquals(array(array('id' => 2, 'name' => 'Bethany')), $users);
		
		$users = $storage->read('users', array('id' => '2'));
		
		$this->assertEquals(array(array('id' => 2, 'name' => 'Bethany')), $users);
	}
	
	public function testInFilter() {
		$storage = $this->storage();
		
		$expected = array(
			array('id' => 1, 'name' => 'Chris'),
			array('id' => 2, 'name' => 'Bethany')
		);
		
		$users = $storage->read('users', array('name' => array('Chris', 'Bethany')));
		
		$this->assertEquals($expected, $users);
		
		$users = $storage->read('users', array('name =' => array('Chris', 'Bethany')));
		
		$this->assertEquals($expected, $users);
		
		$users = $storage->read('users', array('name in' => array('Chris', 'Bethany')));
		
		$this->assertEquals($expected, $users);
	}
	
	public function testNotInFilter() {
		$storage = $this->storage();
		
		$expected = array(
			array('id' => 2, 'name' => 'Bethany')
		);
		
		$users = $storage->read('users', array('name !=' => array('Chris')));
		
		$this->assertEquals($expected, $users);
		
		$users = $storage->read('users', array('name not in' => array('Chris')));
		
		$this->assertEquals($expected, $users);
	}
	
	public function testLikeFilter() {
		$storage = $this->storage();
		
		$roles = $storage->read('roles', array('name like' => '%admin%'));
		
		$this->assertEquals(array(array('id' => 3, 'name' => 'Administrator')), $roles);
		
		$roles = $storage->read('users', array('name like' => '%beth%'));
		
		$this->assertEquals(array(array('id' => 2, 'name' => 'Bethany')), $roles);
	}
	
	public function testOrFilter() {
		$storage = $this->storage();
		
		$roles = $storage->read('roles', array(
			'or' => array(
				'id'        => 2,
				'name like' => '%admin%',
			)
		));
		
		$this->assertEquals(array(
			array('id' => 2, 'name' => 'Moderator'),
			array('id' => 3, 'name' => 'Administrator')
		), $roles);
	}
	
	public function testMultipleValues() {
		$storage = $this->storage();
		
		$roles = $storage->read('roles', array(
			'name like' => array('ad%', '%strator')
		));
		
		$this->assertEquals(array(
			array('id' => 3, 'name' => 'Administrator')
		), $roles);
		
		$roles = $storage->read('roles', array(
			'or' => array(
				'name like' => array('%admin%', '%mod%')
			)
		));
		
		$this->assertEquals(array(
			array('id' => 2, 'name' => 'Moderator'),
			array('id' => 3, 'name' => 'Administrator')
		), $roles);
	}
	
}
