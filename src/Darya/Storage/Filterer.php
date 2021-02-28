<?php
namespace Darya\Storage;

use Darya\Storage\Query\Builder;
use InvalidArgumentException;

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
	 * Filter comparison operators.
	 *
	 * @var string[]
	 */
	protected static array $operators = [
		'=', '!=', '>', '<', '<>', '>=', '<=', 'in', 'not in', 'is', 'is not',
		'like', 'not like'
	];

	/**
	 * A map of filter operators to methods that implement them.
	 *
	 * @var array<string, string>
	 */
	protected static array $methods = [
		'='        => 'equal',
		'!='       => 'notEqual',
		'>'        => 'greater',
		'<'        => 'smaller',
		'<>'       => 'greaterOrSmaller',
		'>='       => 'greaterOrEqual',
		'<='       => 'smallerOrEqual',
		'in'       => 'in',
		'not in'   => 'notIn',
		'is'       => 'is',
		'is not'   => 'isNot',
		'like'     => 'like',
		'not like' => 'notLike'
	];

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
	public static function separateField(string $field): array
	{
		return asplode(' ', trim($field), 2);
	}

	/**
	 * Prepare a default operator for the given value.
	 *
	 * @param string|null $operator
	 * @param mixed       $value
	 * @return string
	 */
	public static function prepareOperator(?string $operator, $value): string
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

		if (is_array($value) || $value instanceof Builder) {
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
	protected static function getComparisonMethod(string $operator): string
	{
		return static::$methods[$operator] ?? 'equal';
	}

	/**
	 * Determine whether the given comparison method handles array values by
	 * itself.
	 *
	 * @param string $method
	 * @return bool
	 */
	protected static function methodHandlesArrays(string $method): bool
	{
		return $method === 'in' || $method === 'notIn';
	}

	/**
	 * Build a closure to use with `array_filter()`.
	 *
	 * @param array $filter
	 * @param bool  $or     [optional]
	 * @return callable
	 */
	public function closure(array $filter, $or = false): callable
	{
		$filterer = $this;

		return function ($row) use ($filterer, $filter, $or) {
			return $filterer->matches($row, $filter, $or);
		};
	}

	/**
	 * Filter the given data.
	 *
	 * @param array $data
	 * @param array $filter [optional]
	 * @return array
	 */
	public function filter(array $data, array $filter = []): array
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
	public function reject(array $data, array $filter = [], $limit = 0): array
	{
		if (empty($filter)) {
			return $data;
		}

		$pruned = array_filter($data, $this->closure($filter));

		if ($limit) {
			$pruned = array_slice($pruned, 0, $limit, true);
		}

		return array_values(array_diff_key($data, $pruned));
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
	public function matches(array $row, array $filter = [], $or = false): bool
	{
		if (empty($filter)) {
			return true;
		}

		$result = false;

		foreach ($filter as $field => $value) {
			[$field, $operator] = static::separateField($field);

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

			$value = $this->prepareValue($method, $value);

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
	 * @param array    $filter
	 * @param callable $callback [optional]
	 * @param int|null $limit    [optional]
	 * @return array
	 */
	public function map(array $data, array $filter, callable $callback, ?int $limit = 0): array
	{
		if (!is_callable($callback)) {
			return $data;
		}

		$affected = 0;

		foreach ($data as $key => $row) {
			if ($this->matches($row, $filter)) {
				$data[$key] = call_user_func_array($callback, [$row, $key]);

				$affected++;
			}

			if ($limit && $affected >= $limit) {
				break;
			}
		}

		return $data;
	}

	/**
	 * Prepare a value for comparison.
	 *
	 * @param string $method Comparison method
	 * @param mixed  $value  Value to prepare
	 * @return mixed The prepared value
	 * @throws InvalidArgumentException If the value is a subquery that returns more or less than one field
	 */
	protected function prepareValue(string $method, $value)
	{
		if (static::methodHandlesArrays($method) && $value instanceof Builder) {
			if (count($value->fields) <> 1) {
				throw new InvalidArgumentException('Filter subqueries should only return one field');
			}

			/**
			 * Extract the values from the query result data
			 *
			 * TODO: {@see \Darya\ORM\Mapper::run()}, it doesn't return a storage result which causes this failure
			 *       We could optionally assume "some array" or "some iterable" but sticking to an interface is
			 *       probably a good idea, as EntityManager and Mapper both break the return value contract of
			 *       the Queryable storage interface currently
			 */
			$result = $value->run();
			var_dump($result);
			$data = $result->data;

			$value = array_reduce($data, function (array $carry, array $row) {
				$carry[] = array_values($row)[0] ?? null;

				return $carry;
			}, []);
		}

		return $value;
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
	protected function compare(string $method, $actual, $value): bool
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
	protected function compareOr(string $method, $actual, $value): bool
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
	 * Determine whether the given values are loosely equal.
	 *
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function equal($actual, $value): bool
	{
		if (is_string($actual)) {
			$actual = strtolower($actual);
		}

		if (is_string($value)) {
			$value = strtolower($value);
		}

		return $actual == $value;
	}

	/**
	 * Determine whether the given values are not loosely equal.
	 *
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function notEqual($actual, $value): bool
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
	protected function greater($actual, $value): bool
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
	protected function smaller($actual, $value): bool
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
	protected function greaterOrsmaller($actual, $value): bool
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
	protected function greaterOrEqual($actual, $value): bool
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
	protected function smallerOrEqual($actual, $value): bool
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
	protected function in($actual, $value): bool
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
	protected function notIn($actual, $value): bool
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
	protected function is($actual, $value): bool
	{
		return $actual === $value;
	}

	/**
	 * Determine the result of a negative strict comparison between the given
	 * values.
	 *
	 * @param mixed $actual
	 * @param mixed $value
	 * @return bool
	 */
	protected function isNot($actual, $value): bool
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
	protected function like($actual, $value): bool
	{
		$value = preg_quote($value, '/');

		$pattern = '/' . preg_replace(['/([^\\\])?_/', '/([^\\\])?%/'], ['$1.', '$1.*'], $value) . '/i';

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
	protected function notLike($actual, $value): bool
	{
		return !$this->like($actual, $value);
	}
}
