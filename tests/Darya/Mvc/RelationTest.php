<?php
use Darya\Mvc\Model;
use Darya\Mvc\Relation;

class Page extends Model {
	
}

class Section extends Model {
	
}

class RelationTest extends PHPUnit_Framework_TestCase {
	
	public function testHasFilter() {
		$page = new Page(array('id' => 1));
		$relation = new Relation($page, 'has', 'Section');
		
		$this->assertEquals(array('page_id' => 1), $relation->filter());
	}
	
	public function testBelongsToFilter() {
		$section = new Section(array('id' => 1, 'page_id' => 2));
		$relation = new Relation($section, 'belongs_to', 'Page');
		
		$this->assertEquals(array('id' => 2), $relation->filter());
	}
}
