<?php
namespace Darya\Storage;

/**
 * Darya's aggregational data store interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Aggregational {
	
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
	 */
	public function distinct($resource, $field, array $filter = array(), $order = array(), $limit = null, $offset = 0);
	
}
