<?php
namespace Darya\Database\Storage\Query;

/**
 * Represents a join from one resource to another.
 *
 * TODO: Formalise join types as constants.
 *
 * @property-read string $type
 * @property-read string $resource
 * @property-read string $alias
 * @property-read array  $conditions
 * @property-read array  $filter
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Join
{
	/**
	 * The type of the join.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The resource to join to.
	 *
	 * @var string
	 */
	protected $resource;

	/**
	 * An alias for the resource to join to.
	 *
	 * @var string
	 */
	protected $alias;

	/**
	 * Condition strings to join on.
	 *
	 * @var string[]
	 */
	protected $conditions = array();

	/**
	 * Conditions, with values, to join on.
	 *
	 * @var mixed[]
	 */
	protected $filter = array();

	/**
	 * Instantiate a new join.
	 *
	 * @param string $type
	 * @param string $resource
	 */
	public function __construct($type, $resource)
	{
		$this->type = $type;

		list($resource, $alias) = static::resolveResource($resource);

		$this->to($resource, $alias);
	}

	/**
	 * Resolve the given resource string to a resource name and optional alias.
	 *
	 * @param string $resource
	 * @return array
	 */
	protected static function resolveResource($resource)
	{
		if (empty($resource)) {
			return array(null, null);
		}

		$alias = null;

		$parts = preg_split('/\s+/', $resource, 3);

		if (count($parts) > 2 && strtolower($parts[1]) === 'as') {
			$alias = $parts[2];
		} else if (count($parts) > 1) {
			$alias = $parts[1];
		}

		$resource = $parts[0];

		return array($resource, $alias);
	}

	/**
	 * Set the resource to join to, optionally providing an alias to use.
	 *
	 * @param string $resource
	 * @param string $alias    [optional]
	 * @return $this
	 */
	public function to($resource, $alias = null)
	{
		$this->resource = $resource;

		$this->alias = $alias ?: $this->alias;

		return $this;
	}

	/**
	 * Add a plain condition string to join on.
	 *
	 * @param string $condition
	 * @return $this
	 */
	public function on($condition)
	{
		$this->conditions[] = $condition;

		return $this;
	}

	/**
	 * Add a complex condition value to join on.
	 *
	 * @param string $field
	 * @param mixed  $value
	 * @return $this
	 */
	public function where($field, $value)
	{
		$this->filter[$field] = $value;

		return $this;
	}

	/**
	 * Dynamically retrieve a property.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		return $this->$property;
	}
}
