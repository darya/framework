<?php
namespace Darya\Storage;

use Darya\Storage\Filterer;
use Darya\Storage\Readable;
use Darya\Storage\Modifiable;

/**
 * Darya's in-memory storage interface.
 * 
 * Useful for unit testing!
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class InMemory implements Readable, Modifiable {
	
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
	 * Create a new in-memory storage interface with the given data.
	 * 
	 * @param array $data [optional]
	 */
	public function __construct(array $data = array()) {
		$this->data = $data;
		$this->filterer = new Filterer;
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
	public function read($resource, array $filter = array(), $order = null, $limit = null, $offset = 0) {
		if (empty($this->data[$resource])) {
			return array();
		}
		
		return $this->filterer->filter($this->data[$resource], $filter);
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
	public function listing($resource, $fields, array $filter = array(), $order = array(), $limit = null, $offset = 0) {
		$data = $this->read($resource, $filter);
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
	public function count($resource, array $filter = array()) {
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
	 */
	public function create($resource, $data) {
		if (!isset($this->data[$resource])) {
			$this->data[$resource] = array();
		}
		
		$this->data[$resource][] = $data;
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
	public function update($resource, $data, array $filter = array(), $limit = null) {
		if (empty($this->data[$resource])) {
			return;
		}
		
		$affected = 0;
		
		$this->data[$resource] = $this->filterer->map($this->data[$resource], $filter,
			function ($row) use ($data, &$affected, $limit) {
				if ($limit && $affected >= $limit) {
					return $row;
				}
				
				foreach ($data as $key => $value) {
					$row[$key] = $value;
				}
				
				$affected++;
				
				return $row;
			}
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
	public function delete($resource, array $filter = array(), $limit = null) {
		if (empty($this->data[$resource])) {
			return;
		}
		
		$this->data[$resource] = $this->filterer->remove($this->data[$resource], $filter);
	}
	
	
	/**
	 * Retrieve the error that occured with the last operation.
	 * 
	 * Returns false if there was no error.
	 * 
	 * @return string|bool
	 */
	public function error() {
		return false;
	}
	
}
