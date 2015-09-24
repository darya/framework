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
	protected $operators = array('=', '!=', '>', '<', '<>', '>=', '<=', 'in', 'not in', 'is', 'is not', 'like', 'not like');
	
	/**
	 * @var array A map of filter operators to methods that implement them
	 */
	protected $methods = array(
		'='  => 'equal',
		'!=' => 'notEqual',
		'>'  => 'greater',
		'<'  => 'smaller',
		'<>' => 'greaterOrSmaller',
		'>=' => 'greaterOrEqual',
		'<=' => 'smallerOrEqual',
		'in' => 'in',
		'not in' => 'notIn',
		'is'     => 'is',
		'is not' => 'isNot',
		'like'   => 'like',
		'not like' => 'notLike'
	);
	
	/**
	 * Escape the given value for use as a like query.
	 * 
	 * Precedes all underscore and percentage characters with a backwards slash.
	 * 
	 * @param string $value
	 * @return string
	 */
	public function escape($value) {
		return preg_replace('/([%_])/', '\\$1', $value);
	}
	
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
			if (strtolower($field) === 'or') {
				$data = $this->processOr($data, $value);
			} else {
				$data = $this->process($data, $field, $value);
			}
		}
		
		return $data;
	}
	
	/**
	 * Separate the given filter field into a field and its operator.
	 * 
	 * Simply splits the given string on the first space found.
	 * 
	 * @param string $field
	 * @return array array($field, $operator)
	 */
	protected function separateField($field) {
		return array_pad(explode(' ', $field, 2), 2, null);
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
	 * Retrieve the comparison method to use for the given operator.
	 * 
	 * Returns 'equals' if the operator is not recognised.
	 * 
	 * @param string $operator
	 * @return string
	 */
	protected function getComparisonMethod($operator) {
		return isset($this->methods[$operator]) ? $this->methods[$operator] : 'equals';
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
		list($field, $operator) = $this->separateField($field);
		
		$operator = $this->prepareOperator($operator, $value);
		
		$method = $this->methods[$operator];
		
		$filterer = $this;
		
		return array_values(array_filter($data, function ($row) use ($filterer, $method, $data, $field, $value) {
			if (!isset($row[$field])) {
				return false;
			}
			
			$actual = $row[$field];
			
			return $filterer->evaluate($method, $actual, $value);
		}));
	}
	
	/**
	 * Process an 'or' filter on the given data.
	 * 
	 * @param array $data
	 * @param array $filter
	 * @return array
	 */
	protected function processOr(array $data, array $filter = array()) {
		if (empty($filter)) {
			return $data;
		}
		
		$filterer = $this;
		
		return array_values(array_filter($data, function ($row) use ($filterer, $filter) {
			$keep = false;
			
			foreach ($filter as $field => $value) {
				list($field, $operator) = $filterer->separateField($field);
				
				if (!isset($row[$field])) {
					continue;
				}
				
				$actual = $row[$field];
				
				$operator = $filterer->prepareOperator($operator, $value);
				
				$method = $filterer->getComparisonMethod($operator);
				
				$keep |= $filterer->evaluateOr($method, $actual, $value);
			}
			
			return $keep;
		}));
	}
	
	/**
	 * Determine the result of the given comparison.
	 * 
	 * If the value is an array, the comparison is evaluated on each element
	 * unless the method supports array values (in() or notIn()).
	 * 
	 * @param string $method
	 * @param mixed  $actual
	 * @param mixed  $value
	 * @return bool
	 */
	protected function evaluate($method, $actual, $value) {
		if (!is_array($value)) {
			return $this->$method($actual, $value);
		}
		
		foreach ($value as $element) {
			if (!$this->$method($actual, $element)) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Determine the result of the given 'or' comparison. Only behaves
	 * differently from evaluate() if $value is an array.
	 * 
	 * @param string $method
	 * @param mixed  $actual
	 * @param mixed  $value
	 * @return bool
	 */
	protected function evaluateOr($method, $actual, $value) {
		if (!is_array($value)) {
			return $this->$method($actual, $value);
		}
		
		$result = false;
		
		foreach ($value as $element) {
			$result |= $this->$method($actual, $element);
		}
		
		return $result;
	}
	
	/**
	 * Determine whether the given values are equal.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function equal($actual, $value) {
		if (is_string($actual) && is_string($value)) {
			$actual = strtolower($actual);
			$value  = strtolower($value);
		}
		
		return $actual == $value;
	}
	
	/**
	 * Determine whether the given values are not equal.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function notEqual($actual, $value) {
		return !$this->equal($actual, $value);
	}
	
	/**
	 * Determine whether the given actual value is greater than the given
	 * comparison value.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function greater($actual, $value) {
		return $actual > $value;
	}
	
	/**
	 * Determine whether the given actual value is smaller than the given
	 * comparison value.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function smaller($actual, $value) {
		return $actual < $value;
	}
	
	/**
	 * Determine whether the given actual value is greater or smaller than the
	 * given comparison value.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function greaterOrsmaller($actual, $value) {
		return $actual <> $value;
	}
	
	/**
	 * Determine whether the given actual value is greater than or equal to the
	 * given comparison value.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function greaterOrEqual($actual, $value) {
		return $actual >= $value;
	}
	
	/**
	 * Determine whether the given actual value is smaller than or equal to the
	 * given comparison value.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function smallerOrEqual($actual, $value) {
		return $actual <= $value;
	}
	
	/**
	 * Determine whether the given actual value is in the given set of values.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function in($actual, $value) {
		return in_array($actual, (array) $value);
	}
	
	/**
	 * Determine whether the given actual value is not in the given set of
	 * values.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function notIn($actual, $value) {
		return !$this->in($actual, $value);
	}
	
	/**
	 * Determine the result of a strict comparison between the given values.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function is($actual, $value) {
		return $actual === $value;
	}
	
	/**
	 * Determine the result of a negative boolean comparison between the given
	 * values.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function isNot($actual, $value) {
		return !$this->is($actual, $value);
	}
	
	/**
	 * Determine whether the given actual value is like the given comparison
	 * value.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function like($actual, $value) {
		$value = preg_quote($value, '/');
		
		$pattern = '/' . preg_replace(array('/([^\\\])?_/', '/([^\\\])?%/'), array('$1.', '$1.*'), $value) . '/i';
		
		return preg_match($pattern, $actual);
	}
	
	/**
	 * Determine whether the given actual value is not like the given comparison
	 * value.
	 * 
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function notLike($actual, $value) {
		return !$this->like($actual, $value);
	}
}
