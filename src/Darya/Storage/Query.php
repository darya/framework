<?php
namespace Darya\Storage;

/**
 * Darya's storage query class.
 * 
 * @property string   $resource
 * @property array    $filter
 * @property array    $order
 * @property int|null $limit
 * @property int      $offset
 * @author Chris Andrew <chris@hexus.io>
 */
class Query {
	
	/**
	 * @var string
	 */
	protected $resource;
	
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
		$this->filter = $filter;
		$this->order  = (array) $order;
		$this->limit  = $limit;
		$this->offset = (int) $offset;
	}
	
	/**
	 * Change the resource to query.
	 * 
	 * @param string $resource
	 */
	public function resource($resource) {
		$this->resource = $resource;
	}
	
	/**
	 * Add a filter to the query.
	 * 
	 * @param string $field
	 * @param mixed  $value
	 */
	public function filter($field, $value) {
		$this->filter = array_merge($this->filter, array($field => $value));
	}
	
	/**
	 * Add a sorting order to the query.
	 * 
	 * $order can be 'asc' or 'desc'.
	 * 
	 * @param string $field
	 * @param string $order [optional]
	 */
	public function order($field, $order = 'asc') {
		$this->order = array_merge($this->order, array($field => $order));
	}
	
	/**
	 * Set a limit on the query.
	 * 
	 * It is possible to optionally set an offset too.
	 * 
	 * @param int|null $limit
	 */
	public function limit($limit, $offset = null) {
		$this->limit = $limit;
		
		if (is_numeric($offset)) {
			$this->offset = (int) $offset;
		}
	}
	
	/**
	 * Set an offset on the query.
	 * 
	 * @param int $offset
	 */
	public function offset($offset) {
		$this->offset = (int) $offset;
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
