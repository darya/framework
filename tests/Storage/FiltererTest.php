<?php
namespace Darya\Tests\Storage;

use PHPUnit_Framework_TestCase;
use Darya\Storage\Filterer;

class FiltererTest extends PHPUnit_Framework_TestCase {
	
	public function testSimpleFilter() {
		$filterer = new Filterer;
		
		$data = array(
			array('number' => 1, 'letter' => 'a'),
			array('number' => 2, 'letter' => 'b'),
			array('number' => 3, 'letter' => 'c')
		);
		
		$expected = array(
			array('number' => 2, 'letter' => 'b'),
			array('number' => 3, 'letter' => 'c')
		);
		
		$filtered = $filterer->filter($data, array('number >' => 1));
		
		$this->assertEquals($expected, $filtered);
	}
	
	public function testMap() {
		$filterer = new Filterer;
		
		$data = array(
			array('number' => 1, 'letter' => 'a'),
			array('number' => 2, 'letter' => 'b'),
			array('number' => 3, 'letter' => 'c')
		);
		
		$expected = array(
			array('number' => 1, 'letter' => 'a'),
			array('number' => 2, 'letter' => 'B'),
			array('number' => 3, 'letter' => 'c')
		);
		
		$mapped = $filterer->map($data, array('letter' => 'b'), function ($row) {
			$row['letter'] = strtoupper($row['letter']);
			
			return $row;
		});
		
		$this->assertEquals($expected, $mapped);
		
		$expected = array(
			array('number' => 1, 'letter' => 'a'),
			array('number' => 3, 'letter' => 'bbb'),
			array('number' => 4, 'letter' => 'ccc')
		);
		
		$mapped = $filterer->map($data, array('number >' => 1), function ($row) {
			$row['number'] += 1;
			$row['letter'] = str_repeat($row['letter'], 3);
			
			return $row;
		});
		
		$this->assertEquals($expected, $mapped);
	}
	
	public function testReject() {
		$filterer = new Filterer;
		
		$data = array(
			array('number' => 1, 'letter' => 'a'),
			array('number' => 2, 'letter' => 'b'),
			array('number' => 3, 'letter' => 'c')
		);
		
		$expected = array(
			array('number' => 1, 'letter' => 'a')
		);
		
		$pruned = $filterer->reject($data, array('number >' => 1));
		
		$this->assertEquals($expected, $pruned);
		
		$expected = array(
			array('number' => 1, 'letter' => 'a'),
			array('number' => 3, 'letter' => 'c')
		);
		
		$pruned = $filterer->reject($data, array('number >' => 1), 1);
		
		$this->assertEquals($expected, $pruned);
	}
	
}
