<?php
namespace Darya\Storage;

interface Searchable {
	
	/**
	 * Search for resources on the given fields using the given criteria.
	 * 
	 * @param string       $resource
	 * @param string       $query
	 * @param array|string $fields
	 * @param array        $filter [optional]
	 * @param array|string $order  [optional]
	 * @param int          $limit  [optional]
	 * @param int          $offset [optional]
	 */
	public function search($resource, $query, $fields, array $filter = array(), $order = array(), $limit = null, $offset = 0);
	
}
