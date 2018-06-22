<?php
namespace Darya\Storage;

/**
 * Filters record sets using an array-based criteria syntax.
 *
 * For filtering in-memory storage.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Filterer
{
	/**
	 * @var array Filter comparison operators
	 */
	protected static $operators = array(
		'=', '!=', '>', '<', '<>', '>=', '<=', 'in', 'not in', 'is', 'is not',
		'like', 'not like'
	);

	/**
	 * @var array A map of filter operators to methods that implement them
	 */
	protected static $methods = array(
		'='  => 'equal',
		'!=' => 'notEqual',
		'>'  => 'greater',
		'<'  => 'smaller',
		'<>' => 'greaterOrSmaller',
		'>=' => 'greaterOrEqual',
		'<=' => 'smallerOrEqual',
		'in'     => 'in',
		'not in' => 'notIn',
		'is'     => 'is',
		'is not' => 'isNot',
		'like'     => 'like',
		'not like' => 'notLike'
	);

	/**
	 * Separate the given filter field into a field and its operator.
	 *
	 * Simply splits the given string on the first space found after trimming
	 * the input.
	 *
	 * Usage:
	 *   list($field, $operator) = $filterer->separateField($field);
	 *
	 * @param string $field
	 * @return array array($field, $operator)
	 */
	public static function separateField($field)
	{
		return array_pad(explode(' ', trim($field), 2), 2, null);
	}

	/**
	 * Prepare a default operator for the given value.
	 *
	 * @param string $operator
	 * @param mixed  $value
	 * @return string
	 */
	public static function prepareOperator($operator, $value)
	{
		$operator = trim($operator);

		$operator = in_array(strtolower($operator), static::$operators) ? $operator : '=';

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
	protected static function getComparisonMethod($operator)
	{
		return isset(static::$methods[$operator]) ? static::$methods[$operator] : 'equal';
	}

	/**
	 * Determine whether the given comparison method handles array values by
	 * itself.
	 *
	 * @param string $method
	 * @return bool
	 */
	protected static function methodHandlesArrays($method)
	{
		return $method === 'in' || $method === 'notIn';
	}

	/**
	 * Build a closure to use with array_filter().
	 *
	 * @param array $filter
	 * @param bool  $or     [optional]
	 * @return \Closure
	 */
	public function closure(array $filter, $or = false)
	{
		$filterer = $this;

		return function ($row) use ($filterer, $filter, $or) {
			return $filterer->matches($row, $filter, $or);
		};
	}

	/**
	 * Escape the given value for use as a like query.
	 *
	 * Precedes all underscore and percentage characters with a backwards slash.
	 *
	 * @param string $value
	 * @return string
	 */
	public function escape($value)
	{
		return preg_replace('/([%_])/', '\\$1', $value);
	}

	/**
	 * Filter the given data.
	 *
	 * @param array $data
	 * @param array $filter [optional]
	 * @return array
	 */
	public function filter(array $data, array $filter = array())
	{
		if (empty($filter)) {
			return $data;
		}

		$data = array_values(array_filter($data, $this->closure($filter)));

		return $data;
	}

	/**
	 * Remove data that matches the given filter.
	 *
	 * @param array $data
	 * @param array $filter [optional]
	 * @param int   $limit  [optional]
	 * @return array
	 */
	public function reject(array $data, array $filter = array(), $limit = 0)
	{
		if (empty($filter)) {
			return $data;
		}

		$keys = array_filter($data, $this->closure($filter));

		if ($limit) {
			$keys = array_slice($keys, 0, $limit, true);
		}

		return array_values(array_diff_key($data, $keys));
	}

	/**
	 * Determine whether a row matches a given filter.
	 *
	 * Optionally applies each filter comparison with 'or' instead of 'and'.
	 *
	 * @param array $row
	 * @param array $filter
	 * @param bool  $or     [optional]
	 * @return bool
	 */
	public function matches(array $row, array $filter = array(), $or = false)
	{
		if (empty($filter)) {
			return true;
		}

		$result = false;

		foreach ($filter as $field => $value) {
			list($field, $operator) = static::separateField($field);

			if (strtolower($field) === 'or') {
				$result = $this->matches($row, $value, true);

				if (!$result) {
					return false;
				}

				continue;
			}

			if (!isset($row[$field])) {
				continue;
			}

			$actual = $row[$field];

			$operator = static::prepareOperator($operator, $value);

			$method = static::getComparisonMethod($operator);

			if ($or) {
				$result |= $this->compareOr($method, $actual, $value);

				continue;
			} else {
				$result = $this->compare($method, $actual, $value);

				if (!$result) {
					return false;
				}
			}
		}

		return $result;
	}

	/**
	 * Apply a function to the elements of the given data that match a filter.
	 *
	 * @param array    $data
	 * @param array    $filter   [optional]
	 * @param callable $callback
	 * @param int      $limit    [optional]
	 * @return array
	 */
	public function map(array $data, array $filter, $callback, $limit = 0)
	{
		if (!is_callable($callback)) {
			return $data;
		}

		$affected = 0;

		foreach ($data as $key => $row) {
			if ($this->matches($row, $filter)) {
				$data[$key] = call_user_func_array($callback, array($row, $key));

				$affected++;
			}

			if ($limit && $affected >= $limit) {
				break;
			}
		}

		return $data;
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
	protected function compare($method, $actual, $value)
	{
		if (!is_array($value) || static::methodHandlesArrays($method)) {
			return $this->$method($actual, $value);
		}

		foreach ($value as $item) {
			if (!$this->$method($actual, $item)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine the result of the given 'or' comparison. Only behaves
	 * differently from compare() if $value is an array.
	 *
	 * @param string $method
	 * @param mixed  $actual
	 * @param mixed  $value
	 * @return bool
	 */
	protected function compareOr($method, $actual, $value)
	{
		if (!is_array($value) || static::methodHandlesArrays($method)) {
			return $this->$method($actual, $value);
		}

		$result = false;

		foreach ($value as $item) {
			$result |= $this->$method($actual, $item);
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
	protected function equal($actual, $value)
	{
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
	protected function notEqual($actual, $value)
	{
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
	protected function greater($actual, $value)
	{
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
	protected function smaller($actual, $value)
	{
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
	protected function greaterOrsmaller($actual, $value)
	{
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
	protected function greaterOrEqual($actual, $value)
	{
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
	protected function smallerOrEqual($actual, $value)
	{
		return $actual <= $value;
	}

	/**
	 * Determine whether the given actual value is in the given set of values.
	 *
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function in($actual, $value)
	{
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
	protected function notIn($actual, $value)
	{
		return !$this->in($actual, $value);
	}

	/**
	 * Determine the result of a strict comparison between the given values.
	 *
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function is($actual, $value)
	{
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
	protected function isNot($actual, $value)
	{
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
	protected function like($actual, $value)
	{
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
	protected function notLike($actual, $value)
	{
		return !$this->like($actual, $value);
	}
}
