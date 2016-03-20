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
 * @property-read StorageQuery $insertSubquery
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Query extends StorageQuery {
	
	/**
	 * Joins to apply to the query.
	 * 
	 * @var Join[]
	 */
	protected $joins = array();
	
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
	public function join($resource, $condition = null, $type = 'inner') {
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
	public function leftJoin($resource, $condition = null) {
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
	public function rightJoin($resource, $condition = null) {
		$this->join($resource, $condition, 'right');
		
		return $this;
	}
	
	/**
	 * Make this a create query with the given subquery.
	 * 
	 * Optionally selects
	 * 
	 * @param StorageQuery $query
	 * @return $this
	 */
	public function createFrom(StorageQuery $query) {
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
	public function insertFrom(StorageQuery $query) {
		$this->modify(static::CREATE);
		$this->createFrom($query);
		
		return $this;
	}
}
