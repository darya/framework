<?php
namespace Darya\Tests\Unit\Storage;

use PHPUnit_Framework_TestCase;
use Darya\Storage\Sorter;

class SorterTest extends PHPUnit_Framework_TestCase {
	
	public function testSort() {
		$sorter = new Sorter;
		
		$data = array(
			array('number' => 3, 'letter' => 'a'),
			array('number' => 4, 'letter' => 'd'),
			array('number' => 4, 'letter' => 'a'),
			array('number' => 1, 'letter' => 'c')
		);
		
		// Ascending order
		$sorted = $sorter->sort($data, 'number');
		
		$expected = array(
			array('number' => 1, 'letter' => 'c'),
			array('number' => 3, 'letter' => 'a'),
			array('number' => 4, 'letter' => 'd'),
			array('number' => 4, 'letter' => 'a')
		);
		
		$this->assertEquals($expected, $sorted);
		
		// Descending order
		$sorted = $sorter->sort($data, array('number' => 'desc'));
		
		$expected = array(
			array('number' => 4, 'letter' => 'd'),
			array('number' => 4, 'letter' => 'a'),
			array('number' => 3, 'letter' => 'a'),
			array('number' => 1, 'letter' => 'c')
		);
		
		$this->assertEquals($expected, $sorted);
		
		// Mixed
		$sorted = $sorter->sort($data, array('number' => 'desc', 'letter'));
		
		$expected = array(
			array('number' => 4, 'letter' => 'a'),
			array('number' => 4, 'letter' => 'd'),
			array('number' => 3, 'letter' => 'a'),
			array('number' => 1, 'letter' => 'c')
		);
		
		$this->assertEquals($expected, $sorted);
		
		// Alternative mixed
		$sorted = $sorter->sort($data, array('letter', 'number'));
		
		$expected = array(
			array('number' => 3, 'letter' => 'a'),
			array('number' => 4, 'letter' => 'a'),
			array('number' => 1, 'letter' => 'c'),
			array('number' => 4, 'letter' => 'd'),
		);
		
		$this->assertEquals($expected, $sorted);
	}
	
}
