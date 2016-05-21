<?php
namespace Darya\Storage;

/**
 * Darya's searchable data store interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Searchable
{
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
	public function search($resource, $query, $fields, array $filter = array(), $order = array(), $limit = null, $offset = 0);
}
