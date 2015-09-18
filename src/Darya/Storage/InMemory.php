<?php
namespace Darya\Storage;

use Darya\Storage\Filterer;
use Darya\Storage\Readable;

/**
 * Darya's in-memory storage interface.
 * 
 * Useful for unit testing!
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class InMemory implements Readable {
	
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
		$data = !empty($this->data[$resource]) ? $this->data[$resource] : array();
		
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
	public function listing($resource, $fields, array $filter = array(), $order = array(), $limit = null, $offset = 0) {
		$data = $this->read($resource);
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
		if (!empty($this->data[$resource]))
			return count($this->data[$resource]);
		
		return 0;
	}
	
}
