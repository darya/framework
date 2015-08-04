<?php
namespace Darya\Storage;

/**
 * Darya's storage query class.
 * 
 * TODO: Maybe make a query interface?
 * 
 * @property string   $resource
 * @property string   $type
 * @property array    $data
 * @property array    $filter
 * @property array    $order
 * @property int|null $limit
 * @property int      $offset
 * @author Chris Andrew <chris@hexus.io>
 */
class Query {
	
	const CREATE = 'create';
	const READ   = 'read';
	const UPDATE = 'update';
	const DELETE = 'delete';
	
	/**
	 * @var string
	 */
	protected $resource;
	
	/**
	 * @var int
	 */
	protected $type;
	
	/**
	 * @var array
	 */
	protected $data;
	
	/**
	 * @var array
	 */
	protected $filter;
	
	/**
	 * @var array
	 */
	protected $order;
	
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
	 * @param string $resource
	 * @param array  $filter   [optional]
	 * @param array  $order    [optional]
	 * @param array  $limit    [optional]
	 * @param array  $offset   [optional]
	 */
	public function __construct($resource, $filter = array(), $order = array(), $limit = null, $offset = 0) {
		$this->resource = $resource;
		$this->type = static::READ;
		$this->data = array();
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
	 * Add a filter to the query.
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
	 * Add an order to the query.
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
