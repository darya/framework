<?php
use Darya\ORM\Record;
use Darya\ORM\Relation;

/**
 * Tests the behaviour of Darya\ORM\Relation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class RelationTest extends PHPUnit_Framework_TestCase {
	
	public function testHasFilter() {
		$page = new Page(array('id' => 1));
		$relation = new Relation\Has($page, 'Section');
		
		$this->assertEquals(array('page_id' => 1), $relation->filter());
		
		$parent = new ParentStub(array('id' => 1));
		$relation = new Relation\Has($parent, 'ChildStub');
		
		$this->assertEquals(array('parent_stub_id' => 1), $relation->filter());
	}
	
	public function testBelongsToFilter() {
		$child = new ChildStub(array('id' => 1, 'parent_stub_id' => 2));
		$relation = new Relation\BelongsTo($child, 'ParentStub');
		
		$this->assertEquals(array('id' => 2), $relation->filter());
	}
	
	public function testBelongsToManyFilter() {
		$page = new Page(array('id' => 3));
		$relation = new Relation\BelongsToMany($page, 'Section');
		
		$this->assertEquals('page_sections', $relation->table());
		$this->assertEquals(array('page_id' => 3), $relation->filter());
		
		$section = new Section(array('id' => 4));
		$relation = new Relation\BelongsToMany($section, 'Page');
		
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
