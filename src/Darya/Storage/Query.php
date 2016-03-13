<?php
namespace Darya\Storage;

use Darya\Storage\Query\Join;

/**
 * Darya's storage query class.
 * 
 * TODO: Maybe make a query interface?
 * TODO: Standardised operators? Think about how this will affect translators.
 * TODO: Move all explicitly SQL-related stuff (joins, aliases) to a new class
 *       that extends this; Database\Storage\Query maybe.
 * 
 * @property bool     $distinct
 * @property string   $resource
 * @property array    $fields
 * @property string   $type
 * @property array    $data
 * @property array    $filter
 * @property array    $order
 * @property int|null $limit
 * @property int      $offset
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Query {
	
	const CREATE = 'create';
	const READ   = 'read';
	const UPDATE = 'update';
	const DELETE = 'delete';
	
	/**
	 * @var bool
	 */
	protected $distinct = false;
	
	/**
	 * @var string
	 */
	protected $resource;
	
	/**
	 * @var array
	 */
	protected $fields;
	
	/**
	 * @var string
	 */
	protected $type;
	
	/**
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * @var array
	 */
	protected $joins = array();
	
	/**
	 * @var array
	 */
	protected $filter = array();
	
	/**
	 * @var array
	 */
	protected $order = array();
	
	/**
	 * @var int|null
	 */
	protected $limit;
	
	/**
	 * @var int
	 */
	protected $offset;
	
	/**
	 * Instantiate a new storage query.
	 * 
	 * @param string   $resource
	 * @param array    $fields   [optional]
	 * @param array    $filter   [optional]
	 * @param array    $order    [optional]
	 * @param int|null $limit    [optional]
	 * @param int      $offset   [optional]
	 */
	public function __construct($resource, array $fields = array(), array $filter = array(), array $order = array(), $limit = null, $offset = 0) {
		$this->resource = $resource;
		$this->fields = $fields;
		$this->type   = static::READ;
		$this->filter = $filter;
		$this->order  = (array) $order;
		$this->limit  = $limit;
		$this->offset = (int) $offset;
	}
	
	/**
	 * Change the type of this query to the given type with optional data.
	 * 
	 * @param mixed $type
	 * @param array $data [optional]
	 */
	protected function modify($type, array $data = array()) {
		$this->type = $type;
		$this->data = $data;
	}
	
	/**
	 * Set the query to result in all data.
	 * 
	 * @return $this
	 */
	public function all() {
		$this->distinct = false;
		
		return $this;
	}
	
	/**
	 * Set the query to result in unique data.
	 * 
	 * @return $this
	 */
	public function distinct() {
		$this->distinct = true;
		
		return $this;
	}
	
	/**
	 * Change the resource to be queried.
	 * 
	 * @param string $resource
	 * @return $this
	 */
	public function resource($resource) {
		$this->resource = $resource;
		
		return $this;
	}
	
	/**
	 * Change the resource fields to retrieve.
	 * 
	 * Used by read queries.
	 * 
	 * @param array $fields [optional]
	 * @return $this
	 */
	public function fields(array $fields = array()) {
		$this->fields = $fields;
		
		return $this;
	}
	
	/**
	 * Make this a create query with the given data.
	 * 
	 * @param array $data
	 * @return $this
	 */
	public function create(array $data) {
		$this->modify(static::CREATE, $data);
		
		return $this;
	}
	
	/**
	 * Make this an update query with the given data.
	 * 
	 * @param array $data
	 * @return $this
	 */
	public function update(array $data) {
		$this->modify(static::UPDATE, $data);
		
		return $this;
	}
	
	/**
	 * Make this a delete query.
	 * 
	 * @return $this
	 */
	public function delete() {
		$this->type = static::DELETE;
		
		return $this;
	}
	
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
	
	/**
	 * Add a filter condition to the query.
	 * 
	 * @param string $field
	 * @param mixed  $value
	 * @return $this
	 */
	public function filter($field, $value) {
		$this->filter = array_merge($this->filter, array($field => $value));
		
		return $this;
	}
	
	/**
	 * Alias for filter().
	 * 
	 * @param string $field
	 * @param mixed  $value
	 * @return $this
	 */
	public function where($field, $value) {
		$this->filter($field, $value);
		
		return $this;
	}
	
	/**
	 * Add an order condition to the query.
	 * 
	 * $order can be 'asc' or 'desc'.
	 * 
	 * @param string $field
	 * @param string $order [optional]
	 * @return $this
	 */
	public function order($field, $order = 'asc') {
		$this->order = array_merge($this->order, array($field => $order));
		
		return $this;
	}
	
	/**
	 * Alias for order().
	 * 
	 * @param string $field
	 * @param string $order [optional]
	 */
	public function sort($field, $order = 'asc') {
		$this->order($field, $order);
		
		return $this;
	}
	
	/**
	 * Set a limit on the query.
	 * 
	 * It is possible to optionally set an offset too.
	 * 
	 * @param int|null $limit
	 * @return $this
	 */
	public function limit($limit, $offset = null) {
		$this->limit = $limit;
		
		if (is_numeric($offset)) {
			$this->offset = (int) $offset;
		}
		
		return $this;
	}
	
	/**
	 * Set an offset on the query.
	 * 
	 * @param int $offset
	 */
	public function offset($offset) {
		$this->offset = (int) $offset;
		
		return $this;
	}
	
	/**
	 * Dynamically retrieve the query's properties.
	 * 
	 * @param string $property
	 */
	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
}
