<?php
use Darya\Storage\Query;

class QueryTest extends PHPUnit_Framework_TestCase {
	
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
	
	public function testReadQuery() {
		
	}
	
	public function testReadQueryFluent() {
		
	}
	
	public function testCreateQuery() {
		
	}
	
	public function testCreateQueryFluent() {
		
	}
	
	public function testUpdateQuery() {
		
	}
	
	public function testUpdateQueryFluent() {
		
	}
	
	public function testDeleteQuery() {
		
	}
	
	public function testDeleteQueryFluent() {
		
	}
	
}
