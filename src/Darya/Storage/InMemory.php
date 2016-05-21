<?php
namespace Darya\Storage;

use Darya\Storage\Aggregational;
use Darya\Storage\Filterer;
use Darya\Storage\Readable;
use Darya\Storage\Modifiable;
use Darya\Storage\Query;
use Darya\Storage\Queryable;
use Darya\Storage\Result;
use Darya\Storage\Searchable;
use Darya\Storage\Sorter;

/**
 * Darya's in-memory storage interface.
 * 
 * Useful for unit testing!
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class InMemory implements Readable, Modifiable, Searchable, Aggregational, Queryable
{
	/**
	 * The in-memory data.
	 * 
	 * @var array
	 */
	protected $data;
	
	/**
	 * Filters results in-memory.
	 * 
	 * @var Filterer
	 */
	protected $filterer;
	
	/**
	 * Sorts results in-memory.
	 * 
	 * @var Sorter
	 */
	protected $sorter;
	
	/**
	 * Create a new in-memory storage interface with the given data.
	 * 
	 * @param array $data [optional]
	 */
	public function __construct(array $data = array())
	{
		$this->data = $data;
		$this->filterer = new Filterer;
		$this->sorter = new Sorter;
	}
	
	/**
	 * Limit the given data to the given length and offset.
	 * 
	 * @param array $data
	 * @param int   $limit  [optional]
	 * @param int   $offset [optional]
	 * @return array
	 */
	protected static function limit(array $data, $limit = 0, $offset = 0)
	{
		return array_slice($data, $offset, $limit ?: null);
	}
	
	/**
	 * Retrieve resource data using the given criteria.
	 * 
	 * Returns an array of associative arrays.
	 * 
	 * @param string       $resource
	 * @param array        $filter   [optional]
	 * @param array|string $order    [optional]
	 * @param int          $limit    [optional]
	 * @param int          $offset   [optional]
	 * @return array
	 */
	public function read($resource, array $filter = array(), $order = array(), $limit = 0, $offset = 0)
	{
		if (empty($this->data[$resource])) {
			return array();
		}
		
		$data = $this->filterer->filter($this->data[$resource], $filter);
		
		$data = $this->sorter->sort($data, $order);
		
		$data = static::limit($data, $limit, $offset);
		
		return $data;
	}
	
	/**
	 * Retrieve specific fields of a resource.
	 * 
	 * Returns an array of associative arrays.
	 * 
	 * @param string       $resource
	 * @param array|string $fields
	 * @param array        $filter   [optional]
	 * @param array|string $order    [optional]
	 * @param int          $limit    [optional]
	 * @param int          $offset   [optional]
	 * @return array
	 */
	public function listing($resource, $fields, array $filter = array(), $order = array(), $limit = 0, $offset = 0)
	{
		$data = $this->read($resource, $filter, $order, $limit, $offset);
		
		if (empty($fields) || $fields === '*') {
			return $data;
		}
		
		$fields = (array) $fields;
		
		$result = array();
		
		foreach ($data as $row) {
			$new = array();
			
			foreach ($row as $field => $value) {
				if (in_array($field, $fields)) {
					$new[$field] = $value;
				}
			}
			
			if (!empty($new)) {
				$result[] = $new;
			}
		}
		
		return $result;
	}
	
	/**
	 * Count the given resource with an optional filter.
	 * 
	 * @param string $resource
	 * @param array  $filter   [optional]
	 * @return int
	 */
	public function count($resource, array $filter = array())
	{
		if (empty($this->data[$resource])) {
			return 0;
		}
		
		return count($this->filterer->filter($this->data[$resource], $filter));
	}
	
	/**
	 * Create resource instances in the data store.
	 * 
	 * @param string $resource
	 * @param array  $data
	 * @return bool
	 */
	public function create($resource, $data)
	{
		if (!isset($this->data[$resource])) {
			$this->data[$resource] = array();
		}
		
		$this->data[$resource][] = $data;
		
		return true;
	}
	
	/**
	 * Update resource instances in the data store.
	 * 
	 * @param string $resource
	 * @param array  $data
	 * @param array  $filter   [optional]
	 * @param int    $limit    [optional]
	 * @return int|bool
	 */
	public function update($resource, $data, array $filter = array(), $limit = 0)
	{
		if (empty($this->data[$resource])) {
			return;
		}
		
		$affected = 0;
		
		$this->data[$resource] = $this->filterer->map(
			$this->data[$resource],
			$filter,
			function ($row) use ($data, &$affected) {
				foreach ($data as $key => $value) {
					$row[$key] = $value;
				}
				
				$affected++;
				
				return $row;
			},
			$limit
		);
		
		return $affected;
	}
	
	/**
	 * Delete resource instances from the data store.
	 * 
	 * @param string $resource
	 * @param array  $filter   [optional]
	 * @param int    $limit    [optional]
	 * @return int|bool
	 */
	public function delete($resource, array $filter = array(), $limit = null)
	{
		if (empty($this->data[$resource])) {
			return;
		}
		
		$this->data[$resource] = $this->filterer->reject($this->data[$resource], $filter);
	}
	
	/**
	 * Search for resource data with fields that match the given query and
	 * criteria.
	 * 
	 * @param string       $resource
	 * @param string       $query
	 * @param array|string $fields
	 * @param array        $filter   [optional]
	 * @param array|string $order    [optional]
	 * @param int          $limit    [optional]
	 * @param int          $offset   [optional]
	 * @return array
	 */
	public function search($resource, $query, $fields, array $filter = array(), $order = array(), $limit = null, $offset = 0)
	{
		if (empty($query) || empty($resource)) {
			return $this->read($resource, $filter, $order, $limit, $offset);
		}
		
		$fields = (array) $fields;
		$search = array('or' => array());
		
		foreach ($fields as $field) {
			$search['or']["$field like"] = "%$query%";
		}
		
		$filter = array_merge($filter, $search);
		
		return $this->read($resource, $filter, $order, $limit, $offset);
	}
	
	/**
	 * Retrieve the distinct values of the given resource's field.
	 * 
	 * Returns a flat array of values.
	 * 
	 * @param string $resource
	 * @param string $field
	 * @param array  $filter   [optional]
	 * @param array  $order    [optional]
	 * @param int    $limit    [optional]
	 * @param int    $offset   [optional]
	 * @return array
	 */
	public function distinct($resource, $field, array $filter = array(), $order = array(), $limit = 0, $offset = 0)
	{
		$list = array();
		
		$listing = $this->listing($resource, $field, $filter, $order, $limit, $offset);
		
		foreach ($listing as $item) {
			$list[] = $item[$field];
		}
		
		return array_unique($list);
	}
	
	/**
	 * Execute the given query.
	 * 
	 * @param Query $query
	 * @return Result
	 */
	public function execute(Query $query)
	{
		$data = array();
		$info = array();
		
		switch ($query->type) {
			case Query::CREATE:
				$this->create($query->resource, $query->data);
				break;
			case Query::READ:
				$data = $this->listing(
					$query->resource,
					$query->fields,
					$query->filter,
					$query->order,
					$query->limit,
					$query->offset
				);
				break;
			case Query:UPDATE:
				$info['affected'] = $this->update(
					$query->resource,
					$query->data,
					$query->filter,
					$query->limit
				);
				break;
			case Query::DELETE:
				$this->delete(
					$query->resource,
					$query->filter,
					$query->limit
				);
				break;
		}
		
		return new Result($query, $data, $info);
	}
	
	/**
	 * Open a query on the given resource.
	 * 
	 * @param string       $resource
	 * @param array|string $fields   [optional]
	 * @return Query\Builder
	 */
	public function query($resource, $fields = array())
	{
		return new Query\Builder(new Query($resource, (array) $fields), $this);
	}
	
	/**
	 * Retrieve the error that occured with the last operation.
	 * 
	 * Returns false if there was no error.
	 * 
	 * @return string|bool
	 */
	public function error()
	{
		return false;
	}
}
