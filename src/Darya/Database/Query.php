<?php
namespace Darya\Database;

/**
 * Darya's immutable database query class.
 *
 * @property-read string $string
 * @property-read array  $parameters
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Query
{
	/**
	 * The SQL query string.
	 *
	 * @var string
	 */
	protected $string;

	/**
	 * Data bound to the query.
	 *
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * Instantiate a new database query.
	 *
	 * @param string $string
	 * @param array  $parameters [optional]
	 */
	public function __construct($string, $parameters = array())
	{
		$this->string = $string;
		$this->parameters = $parameters;
	}

	/**
	 * Dynamically retrieve a property of the query.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		return $this->$property;
	}

	/**
	 * Retrieve the query's string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->string;
	}
}
