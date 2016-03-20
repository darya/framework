<?php
namespace Darya\Storage;

/**
 * Darya's storage query class.
 * 
 * TODO: Maybe make a query interface?
 * TODO: Standardised operators? Think about how this will affect translators.
 * 
 * @property-read bool     $distinct
 * @property-read string   $resource
 * @property-read array    $fields
 * @property-read string   $type
 * @property-read array    $data
 * @property-read array    $filter
 * @property-read array    $order
 * @property-read int|null $limit
 * @property-read int      $offset
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Query {
	
	const CREATE = 'create';
	const READ   = 'read';
	const UPDATE = 'update';
	const DELETE = 'delete';
	
	/**
	 * Determines whether to return unique resource data.
	 * 
	 * @var bool
	 */
	protected $distinct = false;
	
	/**
	 * The resource to query.
	 * 
	 * @var string
	 */
	protected $resource;
	
	/**
	 * The resource fields to retrieve.
	 * 
	 * @var array
	 */
	protected $fields;
	
	/**
	 * The type of the query.
	 * 
	 * @var string
	 */
	protected $type;
	
	/**
	 * The data to create or update resource data with.
	 * 
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * Filters to apply to the query.
	 * 
	 * @var array
	 */
	protected $filter = array();
	
	/**
	 * Fields to sort resource data by.
	 * 
	 * @var array
	 */
	protected $order = array();
	
	/**
	 * Limits the number of rows to retrieve.
	 * 
	 * @var int
	 */
	protected $limit;
	
	/**
	 * Offsets the rows to retrieve.
	 * 
	 * @var int
	 */
	protected $offset;
	
	/**
	 * Instantiate a new storage query.
	 * 
	 * @param string       $resource
	 * @param array|string $fields   [optional]
	 * @param array        $filter   [optional]
	 * @param array        $order    [optional]
	 * @param int          $limit    [optional]
	 * @param int          $offset   [optional]
	 */
	public function __construct($resource, $fields = array(), array $filter = array(), array $order = array(), $limit = null, $offset = 0) {
		$this->type   = static::READ;
		
		$this->resource = $resource;
		$this->fields = (array) $fields;
		$this->filter = $filter;
		$this->order  = $order;
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
	 * @param array|string $fields [optional]
	 * @return $this
	 */
	public function fields($fields = array()) {
		$this->fields = (array) $fields;
		
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
	 * Make this a read query.
	 * 
	 * Optionally accepts the resource fields to retrieve.
	 * 
	 * @param array|string $fields [optional]
	 * @return $this
	 */
	public function read($fields = array()) {
		$this->type = static::READ;
		
		if ($fields) {
			$this->fields($fields);
		}
		
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
	 * Alias for create().
	 * 
	 * @param array $data
	 * @return $this
	 */
	public function insert(array $data) {
		$this->create($data);
		
		return $this;
	}
	
	/**
	 * Alias for read().
	 * 
	 * @param array|string $fields [optional]
	 * @return $this
	 */
	public function select($fields = array()) {
		$this->read($fields);
		
		return $this;
	}
	
	/**
	 * Add a filter condition to the query.
	 * 
	 * @param string $field
	 * @param mixed  $value [optional]
	 * @return $this
	 */
	public function filter($field, $value = null) {
		$this->filter = array_merge($this->filter, array($field => $value));
		
		return $this;
	}
	
	/**
	 * Alias for filter().
	 * 
	 * @param string $field
	 * @param mixed  $value [optional]
	 * @return $this
	 */
	public function where($field, $value = null) {
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
	 * An optional offset can be passed as the second parameter.
	 * 
	 * @param int $limit
	 * @param int $offset [optional]
	 * @return $this
	 */
	public function limit($limit, $offset = 0) {
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
	 * @return this
	 */
	public function offset($offset) {
		$this->offset = (int) $offset;
		
		return $this;
	}
	
	/**
	 * Alias for offset().
	 * 
	 * @param int $offset
	 * @return $this
	 */
	public function skip($offset) {
		$this->offset($offset);
		
		return $this;
	}
	
	/**
	 * Dynamically retrieve a property.
	 * 
	 * @param string $property
	 */
	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
}
