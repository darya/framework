<?php
namespace Darya\Tests\Storage;

use PHPUnit_Framework_TestCase;
use Darya\Storage\Error;
use Darya\Storage\Query;
use Darya\Storage\Result;

class ResultTest extends PHPUnit_Framework_TestCase {
	
	public function testSimpleResult() {
		$query = new Query('users');
		$result = new Result($query);
		
		$this->assertSame($query, $result->query);
		$this->assertSame(array(), $result->data);
		$this->assertSame(0, $result->count);
		$this->assertSame(0, $result->affected);
		$this->assertNull($result->error);
		$this->assertSame(array(), $result->fields);
	}
	
	public function testGetResultInfo() {
		$query = new Query('users');
		
		$result = new Result($query, array(), array(
			'count' => 1,
			'affected' => 2,
			'fields' => array('one', 'two'),
			'insert_id' => 3
		));
		
		$info = $result->getInfo();
		
		$this->assertEquals(1, $info['count']);
		$this->assertEquals(2, $info['affected']);
		$this->assertEquals(array('one', 'two'), $info['fields']);
		$this->assertEquals(3, $info['insert_id']);
	}
	
	public function testSelectResultInfo() {
		$query = new Query('users');
		$data = array(
			array('id' => 1, 'name' => 'Chris')
		);
		$info = array(
			'count' => 1,
			'fields' => array('id', 'name')
		);
		
		$result = new Result($query, $data, $info);
		
		$this->assertSame($query, $result->query);
		$this->assertSame($data, $result->data);
		$this->assertSame(1, $result->count);
		$this->assertSame(0, $result->affected);
		$this->assertSame(0, $result->insertId);
		$this->assertNull($result->error);
		$this->assertSame($info['fields'], $result->fields);
	}
	
	public function testInsertResultInfo() {
		$query = new Query('users');
		$data = array(
			array('name' => 'Chris')
		);
		$query->create($data);
		
		$info = array(
			'affected' => 1,
			'insert_id' => 1
		);
		
		$result = new Result($query, array(), $info);
		
		$this->assertEquals(1, $result->affected);
		$this->assertEquals(1, $result->insertId);
	}
	
	public function testResultError() {
		$query = new Query('users');
		$error = new Error(1, 'Some error');
		
		$result = new Result($query, array(), array(), $error);
		
		$this->assertSame($error, $result->error);
	}
	
}