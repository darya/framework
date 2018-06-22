<?php
namespace Darya\Storage;

/**
 * Darya's readable data store interface.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Readable
{
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
	public function read($resource, array $filter = array(), $order = null, $limit = null, $offset = 0);

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
	public function listing($resource, $fields, array $filter = array(), $order = array(), $limit = null, $offset = 0);

	/**
	 * Count the given resource with an optional filter.
	 *
	 * @param string $resource
	 * @param array  $filter   [optional]
	 * @return int
	 */
	public function count($resource, array $filter = array());
}
