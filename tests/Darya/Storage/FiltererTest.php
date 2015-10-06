<?php
use Darya\Storage\Filterer;

class FiltererTest extends PHPUnit_Framework_TestCase {
	
	public function testMap() {
		$filterer = new Filterer;
		
		$data = array(
			array(
				'letter' => 'a'
			),
			array(
				'letter' => 'b'
			),
			array(
				'letter' => 'c'
			)
		);
		
		$expected = array(
			array(
				'letter' => 'a'
			),
			array(
				'letter' => 'B'
			),
			array(
				'letter' => 'c'
			)
		);
		
		$mapped = $filterer->map($data, array('letter' => 'b'), function ($row) {
			$row['letter'] = strtoupper($row['letter']);
			
			return $row;
		});
		
		$this->assertEquals($expected, $mapped);
	}
	
}
