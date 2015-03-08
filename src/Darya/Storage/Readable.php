<?php
namespace Darya\Storage;

/**
 * Darya's readable data store interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Readable {
	
	/**
	 * Retrieve resource data using the given criteria.
	 * 
	 * @param string       $resource
	 * @param array        $filter
	 * @param array|string $order
	 * @param int          $limit
	 * @param int          $offset
	 * @return array
	 */
	public function read($resource, array $filter = array(), $order = null, $limit = null, $offset = 0);
	
	/**
	 * Count the given resource using the given filter.
	 * 
	 * @param string $resource
	 * @param array  $filter
	 * @return int
	 */
	public function count($resource, array $filter = array());
	
}