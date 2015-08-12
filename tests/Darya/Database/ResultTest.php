<?php
use Darya\Database\Error;
use Darya\Database\Result;

class ResultTest extends PHPUnit_Framework_TestCase {
	
	public function testSelectResult() {
		$data =  array(
			array(
				'id' => 1,
				'swag' => 'swag'
			),
			array(
				'id' => 2,
				'swag' => 'swag'
			),
			array(
				'id' => 3,
				'swag' => 'swag'
			)
		);
		
		$info = array(
			'count' => 3,
			'fields' => array('id', 'swag')
		);
		
		$result = new Result('SELECT * FROM swag', array(), $data, $info);
		
		$this->assertEquals('SELECT * FROM swag', $result->query);
		$this->assertEquals($data, $result->data);
		$this->assertEquals(3, $result->count);
		$this->assertEquals(array('id', 'swag'), $result->fields);
	}
	
	public function testUpdateResult() {
		$info = array(
			'affected' => 3
		);
		
		$result = new Result("UPDATE swag SET swag='b0ss'", array(), array(), $info);
		
		$this->assertEquals("UPDATE swag SET swag='b0ss'", $result->query);
		$this->assertEquals(0, $result->count);
		$this->assertEquals(3, $result->affected);
		$this->assertEquals(0, $result->insertId);
		$this->assertEquals(array(), $result->fields);
	}
	
	public function testInsertResult() {
		$info = array(
			'affected'  => 1,
			'insert_id' => 4
		);
		
		$result = new Result("INSERT INTO swag (swag) VALUES ('b0ss')", array(), array(), $info);
		
		$this->assertEquals("INSERT INTO swag (swag) VALUES ('b0ss')", $result->query);
		$this->assertEquals(0, $result->count);
		$this->assertEquals(1, $result->affected);
		$this->assertEquals(4, $result->insertId);
		$this->assertEquals(array(), $result->fields);
	}
	
	public function testErrorResult() {
		$info = array();
		
		$error = new Error(1007, "Can't create database 'swag'; database exists");
		$result = new Result('CREATE TABLE swag (id INT UNSIGNED, swag TEXT)', array(), array(), $info, $error);
		
		$this->assertEquals(1007, $result->error->number);
		$this->assertEquals("Can't create database 'swag'; database exists", $result->error->message);
	}
	
}
