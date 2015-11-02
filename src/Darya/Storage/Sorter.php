<?php
namespace Darya\Storage;

/**
 * Sorts record sets using an array-based syntax.
 * 
 * For sorting in-memory storage.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Sorter {
	
	/**
	 * Normalize the given sorting order array.
	 * 
	 * @param array $order
	 * @return array
	 */
	protected static function normalizeOrder(array $order = array()) {
		$result = array();
		
		foreach ($order as $key => $value) {
			if (!is_string($key)) {
				$result[$value] = 'asc';
				
				continue;
			}
			
			$result[$key] = strtolower($value);
		}
		
		return $result;
	}
	
	/**
	 * Prepare the given sorting order.
	 * 
	 * @param array|string $order
	 * @return array
	 */
	protected static function prepareOrder($order) {
		if (is_array($order)) {
			return static::normalizeOrder($order);
		}
		
		if (!is_string($order)) {
			return array();
		}
		
		return array($order => 'asc');
	}
	
	/**
	 * Sort data using the given order.
	 * 
	 * Uses indexes to retain order of equal elements - stable sorting.
	 * 
	 * @param array        $data
	 * @param array|string $order [optional]
	 * @return array
	 */
	public function sort(array $data, $order = array()) {
		$order = $this->prepareOrder($order);
		
		if (empty($order)) {
			return $data;
		}
		
		$index = 0;
		
		foreach ($data as &$item) {
			$item = array($index++, $item);
		}
		
		usort($data, function($a, $b) use ($order) {
			foreach ($order as $field => $direction) {
				if (!isset($a[1][$field]) || !isset($b[1][$field])) {
					continue;
				}
				
				$result = strnatcasecmp($a[1][$field], $b[1][$field]);
				
				if ($result === 0) {
					continue;
				}
				
				if ($direction === 'desc') {
					return $result - $result * 2;
				}
				
				return $result;
			}
			
			return $a[0] - $b[0];
		});
		
		foreach ($data as &$item) {
			$item = $item[1];
		}
		
		return $data;
	}
	
}
