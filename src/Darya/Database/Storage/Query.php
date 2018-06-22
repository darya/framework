<?php
namespace Darya\Database\Storage;

use Darya\Database\Storage\Query\Join;
use Darya\Storage\Query as StorageQuery;

/**
 * Darya's database storage query representation.
 *
 * Provides joins and subqueries.
 *
 * @property-read Join[]       $joins
 * @property-read string[]     $groupings
 * @property-read array        $having
 * @property-read StorageQuery $insertSubquery
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Query extends StorageQuery
{
	/**
	 * Joins to apply to the query.
	 *
	 * @var Join[]
	 */
	protected $joins = array();

	/**
	 * The set of fields to group results by.
	 *
	 * @var string[]
	 */
	protected $groupings = array();

	/**
	 * Filters to apply to results after grouping.
	 *
	 * @var array
	 */
	protected $having = array();

	/**
	 * The subquery to use for the insert query.
	 *
	 * @var StorageQuery
	 */
	protected $insertSubquery;

	/**
	 * Add an inner join to the query.
	 *
	 * @param string $resource
	 * @param mixed  $condition [optional]
	 * @param string $type      [optional]
	 * @return $this
	 */
	public function join($resource, $condition = null, $type = 'inner')
	{
		$join = new Join($type, $resource);

		if (is_callable($condition)) {
			call_user_func($condition, $join);
		} else {
			$join->on($condition);
		}

		$this->joins[] = $join;

		return $this;
	}

	/**
	 * Add a left join to the query.
	 *
	 * @param string $resource
	 * @param mixed  $condition
	 * @return $this
	 */
	public function leftJoin($resource, $condition = null)
	{
		$this->join($resource, $condition, 'left');

		return $this;
	}

	/**
	 * Add a right join to the query.
	 *
	 * @param string $resource
	 * @param mixed  $condition
	 * @return $this
	 */
	public function rightJoin($resource, $condition = null)
	{
		$this->join($resource, $condition, 'right');

		return $this;
	}

	/**
	 * Add a grouping to the query.
	 *
	 * @param string $field
	 * @return $this
	 */
	public function group($field)
	{
		$this->groupings[] = $field;

		$this->groupings = array_keys(array_count_values($this->groupings));

		return $this;
	}

	/**
	 * Add a set of groupings to the query.
	 *
	 * @param string[] $fields
	 * @return $this
	 */
	public function groupings($fields)
	{
		$this->groupings = array_merge($this->groupings, $fields);

		return $this;
	}

	/**
	 * Add a "having" filter condition to the query.
	 *
	 * @param string $field
	 * @param mixed  $value [optional]
	 * @return $this
	 */
	public function having($field, $value = null)
	{
		$this->having = array_merge($this->having, array($field => $value));

		return $this;
	}

	/**
	 * Add multiple "having" filter conditions to the query.
	 *
	 * @param array $filters
	 * @return $this
	 */
	public function havings(array $filters = array())
	{
		$this->having = array_merge($this->having, $filters);

		return $this;
	}

	/**
	 * Make this a create query with the given subquery.
	 *
	 * @param StorageQuery $query
	 * @return $this
	 */
	public function createFrom(StorageQuery $query)
	{
		$this->modify(static::CREATE);
		$this->insertSubquery = $query;

		return $this;
	}

	/**
	 * Alias for createFrom().
	 *
	 * @param StorageQuery $query
	 * @return $this
	 */
	public function insertFrom(StorageQuery $query)
	{
		$this->createFrom($query);

		return $this;
	}
}
