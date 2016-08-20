<?php
namespace Darya\Tests\ORM;

use PHPUnit_Framework_TestCase;

use Darya\ORM\Record;
use Darya\ORM\Relation;

use Darya\Tests\ORM\Fixtures\ChildStub;
use Darya\Tests\ORM\Fixtures\Page;
use Darya\Tests\ORM\Fixtures\ParentStub;
use Darya\Tests\ORM\Fixtures\Section;

/**
 * Tests the behaviour of Darya\ORM\Relation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class RelationTest extends PHPUnit_Framework_TestCase
{
	public function testHasFilter()
	{
		$page = new Page(array('id' => 1));
		$relation = new Relation\Has($page, Section::class);
		
		$this->assertEquals(array('page_id' => 1), $relation->filter());
		
		$parent = new ParentStub(array('id' => 1));
		$relation = new Relation\Has($parent, ChildStub::class);
		
		$this->assertEquals(array('parent_stub_id' => 1), $relation->filter());
	}
	
	public function testHasManyFilter()
	{
		
	}
	
	public function testBelongsToFilter()
	{
		$child = new ChildStub(array('id' => 1, 'parent_stub_id' => 2));
		$relation = new Relation\BelongsTo($child, ParentStub::class);
		
		$this->assertEquals(array('id' => 2), $relation->filter());
	}
	
	public function testBelongsToManyAssociationFilter()
	{
		$page = new Page(array('id' => 3));
		$relation = new Relation\BelongsToMany($page, Section::class);
		
		$this->assertEquals('page_sections', $relation->table());
		$this->assertEquals(array('page_id' => 3), $relation->associationFilter());
		
		$section = new Section(array('id' => 4));
		$relation = new Relation\BelongsToMany($section, Page::class);
		
		$this->assertEquals('page_sections', $relation->table());
		$this->assertEquals(array('section_id' => 4), $relation->associationFilter());
	}
}
