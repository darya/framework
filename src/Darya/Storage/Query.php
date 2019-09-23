<?php

namespace Darya\Storage;

use InvalidArgumentException;

/**
 * Darya's storage query class.
 *
 * TODO: Maybe make a query interface?
 * TODO: Formalise filters and orders?
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
class Query
{
	/**
	 * The create query type.
	 *
	 * @var string
	 */
	const CREATE = 'create';

	/**
	 * The read query type.
	 *
	 * @var string
	 */
	const READ = 'read';

	/**
	 * The update query type.
	 *
	 * @var string
	 */
	const UPDATE = 'update';

	/**
	 * The delete query type.
	 *
	 * @var string
	 */
	const DELETE = 'delete';

	/**
	 * Whether the query results should be unique.
	 *
	 * TODO: Rename to $unique.
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
	protected $data = [];

	/**
	 * Filters to apply to the query.
	 *
	 * @var array
	 */
	protected $filter = [];

	/**
	 * Fields to sort resource data by.
	 *
	 * @var array
	 */
	protected $order = [];

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
	 * @param string       $resource The resource to query.
	 * @param array|string $fields   [optional] The resource fields to retrieve.
	 * @param array        $filter   [optional] Filters to apply to the query.
	 * @param array        $order    [optional] Fields to sort resource data by.
	 * @param int          $limit    [optional] Limits the number of rows to retrieve.
	 * @param int          $offset   [optional] Offsets the rows to retrieve.
	 */
	public function __construct($resource, $fields = [], array $filter = [], array $order = [], $limit = 0, $offset = 0)
	{
		$this->type = static::READ;

		$this->resource = $resource;
		$this->fields   = (array) $fields;
		$this->filter   = $filter;
		$this->order    = $order;
		$this->limit    = (int) $limit;
		$this->offset   = (int) $offset;
	}

	/**
	 * Change the type of this query to the given type with optional data.
	 *
	 * @param mixed $type The type of query to change to.
	 * @param array $data [optional] The data to set.
	 */
	protected function modify($type, array $data = [])
	{
		$this->type = $type;
		$this->data = $data;
	}

	/**
	 * Set the query to result in all data.
	 *
	 * @return $this
	 */
	public function all()
	{
		$this->distinct = false;

		return $this;
	}

	/**
	 * Make the query results unique.
	 *
	 * @return $this
	 */
	public function unique()
	{
		$this->distinct = true;

		return $this;
	}

	/**
	 * Alias for unique().
	 *
	 * @return $this
	 * @see Query::unique()
	 */
	public function distinct()
	{
		$this->unique();

		return $this;
	}

	/**
	 * Set the resource to query.
	 *
	 * @param string $resource
	 * @return $this
	 */
	public function resource(string $resource): Query
	{
		$this->resource = $resource;

		return $this;
	}

	/**
	 * Alias for resource().
	 *
	 * @param string $resource
	 * @return $this
	 * @see Query::resource()
	 */
	public function from(string $resource): Query
	{
		return $this->resource($resource);
	}

	/**
	 * Change the resource fields to retrieve.
	 *
	 * Used by read queries.
	 *
	 * @param array|string $fields [optional]
	 * @return $this
	 */
	public function fields($fields = [])
	{
		$this->fields = (array) $fields;

		return $this;
	}

	/**
	 * Make this a create query with the given data.
	 *
	 * @param array $data
	 * @return $this
	 */
	public function create(array $data): Query
	{
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
	public function read($fields = []): Query
	{
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
	public function update(array $data): Query
	{
		$this->modify(static::UPDATE, $data);

		return $this;
	}

	/**
	 * Make this a delete query.
	 *
	 * @return $this
	 */
	public function delete(): Query
	{
		$this->type = static::DELETE;

		return $this;
	}

	/**
	 * Alias for create().
	 *
	 * @param array $data
	 * @return $this
	 * @see Query::create()
	 */
	public function insert(array $data): Query
	{
		$this->create($data);

		return $this;
	}

	/**
	 * Alias for read().
	 *
	 * @param array|string $fields [optional]
	 * @return $this
	 * @see Query::read()
	 */
	public function select($fields = []): Query
	{
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
	public function filter($field, $value = null): Query
	{
		$this->filter = array_merge($this->filter, [$field => $value]);

		return $this;
	}

	/**
	 * Add multiple filter conditions to the query.
	 *
	 * @param array $filters
	 * @return $this
	 */
	public function filters(array $filters = []): Query
	{
		$this->filter = array_merge($this->filter, $filters);

		return $this;
	}

	/**
	 * Alias for filter().
	 *
	 * @param string $field
	 * @param mixed  $value [optional]
	 * @return $this
	 */
	public function where($field, $value = null): Query
	{
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
	public function order($field, $order = 'asc'): Query
	{
		$this->order = array_merge($this->order, [$field => $order]);

		return $this;
	}

	/**
	 * Add multiple order conditions to the query.
	 *
	 * TODO: Simplify.
	 *
	 * @param array $orders
	 * @return $this
	 */
	public function orders(array $orders = []): Query
	{
		$prepared = [];

		foreach ($orders as $field => $order) {
			if (is_numeric($field)) {
				$prepared[$order] = 'asc';
			} else {
				$prepared[$field] = $order;
			}
		}

		$this->order = array_merge($this->order, $prepared);

		return $this;
	}

	/**
	 * Alias for order().
	 *
	 * @param string $field
	 * @param string $order [optional]
	 * @return $this
	 */
	public function sort($field, $order = 'asc'): Query
	{
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
	public function limit($limit, $offset = 0): Query
	{
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
	 * @return $this
	 */
	public function offset($offset): Query
	{
		$this->offset = (int) $offset;

		return $this;
	}

	/**
	 * Alias for offset().
	 *
	 * @param int $offset
	 * @return $this
	 */
	public function skip($offset): Query
	{
		$this->offset($offset);

		return $this;
	}

	/**
	 * Dynamically retrieve a property.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get(string $property)
	{
		if (property_exists($this, $property)) {
			return $this->$property;
		}

		$class = static::class;

		throw new InvalidArgumentException("Undefined property $class::$property");
	}

	/**
	 * Copy the given query.
	 *
	 * @param Query $query The query to copy from.
	 * @return $this
	 */
	public function copyFrom(Query $query): Query
	{
		$this->distinct = $query->distinct;
		$this->resource = $query->resource;
		$this->fields   = $query->fields;
		$this->type     = $query->type;
		$this->data     = $query->data;
		$this->filter   = $query->filter;
		$this->order    = $query->order;
		$this->limit    = $query->limit;
		$this->offset   = $query->offset;

		return $this;
	}
}
