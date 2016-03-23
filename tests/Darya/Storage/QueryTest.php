<?php
use Darya\Storage\Query;

class QueryTest extends PHPUnit_Framework_TestCase {
	
	public function testDistinct() {
		$query = (new Query('users'))->distinct();
		
		$this->assertTrue($query->distinct);
		
		$query->all();
		
		$this->assertFalse($query->distinct);
	}
	
	public function testFields() {
		$query = new Query('users', 'id');
		
		$this->assertEquals(array('id'), $query->fields);
		
		$query->fields('test');
		
		$this->assertEquals(array('test'), $query->fields);
		
		$query->fields(array('one', 'two'));
		
		$this->assertEquals(array('one', 'two'), $query->fields);
		
		$query->fields();
		
		$this->assertEquals(array(), $query->fields);
		
		$query->fields(null);
		
		$this->assertEquals(array(), $query->fields);
	}
	
	public function testFilter() {
		$query = new Query('users');
		
		$query->filter('this >=', 'that');
		
		$this->assertEquals(array('this >=' => 'that'), $query->filter);
		
		$query->filter('name like', 'chris');
		
		$this->assertEquals(array(
			'this >='   => 'that',
			'name like' => 'chris'
		), $query->filter);
		
		$query->filters(array(
			'test' => '1',
			'test2' => 2,
		));
		
		$this->assertEquals(array(
			'this >='   => 'that',
			'name like' => 'chris',
			'test' => '1',
			'test2' => 2
		), $query->filter);
		
		$query->filter('test', 1);
		
		$this->assertEquals(array(
			'this >='   => 'that',
			'name like' => 'chris',
			'test' => 1,
			'test2' => 2
		), $query->filter);
	}
	
	public function testOrder() {
		$query = new Query('users');
		
		$query->order('name');
		
		$this->assertEquals(array('name' => 'asc'), $query->order);
		
		$query->order('test');
		$query->order('name', 'desc');
		
		$this->assertEquals(array('name' => 'desc', 'test' => 'asc'), $query->order);
		
		$query->order('test', 'blah');
		
		$this->assertEquals(array('name' => 'desc', 'test' => 'blah'), $query->order);
		
		$query = new Query('users');
		
		$query->orders(array(
			'name',
			'test' => 'desc',
			'size' => 'blah'
		));
		
		$this->assertEquals(array('name' => 'asc', 'test' => 'desc', 'size' => 'blah'), $query->order);
	}
	
}
