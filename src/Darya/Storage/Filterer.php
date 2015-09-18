<?php
namespace Darya\Storage;

/**
 * Filters storage results in-memory.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Filterer {
	
	/**
	 * @var array Filter comparison operators
	 */
	protected $operators = array('>=', '<=', '>', '<', '=', '!=', '<>', 'in', 'not in', 'is', 'is not', 'like', 'not like');
	
	/**
	 * @var array A map of filter operators to methods that implement them
	 */
	protected $methods = array(
		'>=' => 'greaterOrEqual',
		'<=' => 'smallerOrEqual',
		'>'  => 'greater',
		'<'  => 'smaller',
		'='  => 'equal',
		'!=' => 'notEqual',
		'<>' => 'greaterOrSmaller',
		'in' => 'in',
		'not in' => 'notIn',
		'is'     => 'is',
		'is not' => 'isNot',
		'like'   => 'like',
		'not like' => 'notLike'
	);
	
	/**
	 * Filter the given data.
	 * 
	 * @param array $data
	 * @param array $filter
	 * @return array
	 */
	public function filter(array $data, array $filter = array()) {
		if (empty($filter)) {
			return $data;
		}
		
		foreach ($filter as $field => $value) {
			$data = $this->process($data, $field, $value);
		}
		
		return $data;
	}
	
	/**
	 * Prepare a default operator for the given value.
	 * 
	 * @param string $operator
	 * @param mixed  $value
	 * @return string
	 */
	protected function prepareOperator($operator, $value) {
		$operator = in_array(strtolower($operator), $this->operators) ? $operator : '=';
		
		if ($value === null) {
			if ($operator === '=') {
				$operator = 'is';
			}
			
			if ($operator === '!=') {
				$operator = 'is not';
			}
		}
		
		if (is_array($value)) {
			if ($operator === '=') {
				$operator = 'in';
			}
		
			if ($operator === '!=') {
				$operator = 'not in';
			}
		}
		
		return $operator;
	}
	
	/**
	 * Process part of a filter on the given data.
	 * 
	 * @param array  $data
	 * @param string $field
	 * @param mixed  $value
	 * @return array
	 */
	protected function process(array $data, $field, $value) {
		list($field, $operator) = array_pad(explode(' ', $field, 2), 2, null);
		
		$operator = $this->prepareOperator($operator, $value);
		
		$method = $this->methods[$operator];
		
		return $this->$method($data, $field, $value);
	}
	
	/**
	 * Filter the given data down to rows with a field that equals the given
	 * value.
	 * 
	 * @param array  $data
	 * @param string $field
	 * @param mixed  $value
	 * @return array
	 */
	protected function equal(array $data, $field, $value) {
		$result = array();
		
		foreach ($data as $row) {
			if (!isset($row[$field])) {
				continue;
			}
			
			$actual = $row[$field];
			
			if (is_string($actual) && is_string($value)) {
				$actual = strtolower($actual);
				$value  = strtolower($value);
			}
			
			if ($actual == $value) {
				$result[] = $row;
			}
		}
		
		return $result;
	}
}
