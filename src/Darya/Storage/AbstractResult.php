<?php

namespace Darya\Storage;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Darya's abstract storage query result.
 *
 * @property-read array  $data     Result data
 * @property-read object $query    Query that produced this result
 * @property-read int    $count    Result count
 * @property-read object $error    Result error
 * @property-read array  $fields   Field names for each result data row
 * @property-read int    $insertId Insert ID
 * @property-read int    $affected Rows affected
 *
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class AbstractResult implements IteratorAggregate
{
	/**
	 * The query that produced this result.
	 *
	 * @var object
	 */
	protected $query;

	/**
	 * An associative array of the result data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The error that occurred when executing the query, if any.
	 *
	 * @var object
	 */
	protected $error;

	/**
	 * The number of rows in the result data.
	 *
	 * @var int
	 */
	protected $count = 0;

	/**
	 * The set of fields available for each row in the result.
	 *
	 * @var array
	 */
	protected $fields = [];

	/**
	 * The number of rows affected by the query.
	 *
	 * @var string
	 */
	protected $affected = 0;

	/**
	 * Auto incremented primary key of an inserted row.
	 *
	 * @var int
	 */
	protected $insertId = 0;

	/**
	 * Convert a string from snake_case to camelCase.
	 *
	 * @param string $string
	 * @return string
	 */
	protected static function snakeToCamel(string $string)
	{
		return preg_replace_callback('/_(.)/', function ($matches) {
			return strtoupper($matches[1]);
		}, $string);
	}

	/**
	 * Get the result info.
	 *
	 * Retrieves the affected, count, insertId and fields properties
	 * as a key-value array, with snake-case equivalent keys.
	 *
	 * @return array
	 */
	public function getInfo()
	{
		return [
			'count'     => $this->count,
			'fields'    => $this->fields,
			'affected'  => $this->affected,
			'insert_id' => $this->insertId
		];
	}

	/**
	 * Set the result info.
	 *
	 * Accepts the keys 'affected', 'count', 'insert_id' and 'fields'.
	 *
	 * @param array $info
	 */
	protected function setInfo(array $info)
	{
		$keys = array_keys($this->getInfo());

		foreach ($keys as $key) {
			$property = static::snakeToCamel($key);

			if (isset($info[$key])) {
				$this->$property = $info[$key];
			}
		}
	}

	/**
	 * Retrieve an external iterator.
	 *
	 * @return Traversable
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}

	/**
	 * Dynamically retrieve the given property.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get(string $property)
	{
		return $this->$property;
	}

	/**
	 * Dynamically determine whether the given property exists.
	 *
	 * @param string $property
	 * @return bool
	 */
	public function __isset(string $property)
	{
		return isset($this->$property);
	}
}
