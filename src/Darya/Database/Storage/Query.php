<?php
namespace Darya\Database\Storage;

use Darya\Database\Storage\Query\Join;
use Darya\Storage\Query as StorageQuery;

/**
 * Darya's database storage query representation.
 * 
 * Provides joins and subqueries.
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
	 */
	public function leftJoin($resource, $condition = null) {
		$this->join($resource, $condition, 'left');
	}
	
	/**
	 * Add a right join to the query.
	 * 
	 * @param string $resource
	 * @param mixed  $condition
	 */
	public function rightJoin($resource, $condition = null) {
		$this->join($resource, $condition, 'right');
	}
}
