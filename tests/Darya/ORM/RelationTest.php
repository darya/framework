<?php
use Darya\ORM\Record;
use Darya\ORM\Relation;

class RelationTest extends PHPUnit_Framework_TestCase {
	
	public function testHasFilter() {
		$page = new Page(array('id' => 1));
		$relation = new Relation($page, 'has', 'Section');
		
		$this->assertEquals(array('page_id' => 1), $relation->filter());
		
		$parent = new ParentStub(array('id' => 1));
		$relation = new Relation($parent, 'has', 'ChildStub');
		
		$this->assertEquals(array('parent_stub_id' => 1), $relation->filter());
	}
	
	public function testBelongsToFilter() {
		$child = new ChildStub(array('id' => 1, 'parent_stub_id' => 2));
		$relation = new Relation($child, 'belongs_to', 'ParentStub');
		
		$this->assertEquals(array('id' => 2), $relation->filter());
	}
	
	public function testManyToMany() {
		$page = new Page(array('id' => 3));
		$relation = new Relation($page, 'belongs_to_many', 'Section');
		
		$this->assertEquals('page_sections', $relation->table());
		$this->assertEquals(array('page_id' => 3), $relation->filter());
		
		$section = new Section(array('id' => 4));
		$relation = new Relation($section, 'belongs_to_many', 'Page');
		
		$this->assertEquals('page_sections', $relation->table());
		$this->assertEquals(array('section_id' => 4), $relation->filter());
	}
}

class Page extends Record {
	
}

class Section extends Record {
	
}

class ParentStub extends Record {
	
}

class ChildStub extends Record {
	
}
