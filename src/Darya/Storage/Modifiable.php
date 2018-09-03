<?php
namespace Darya\Storage;

/**
 * Darya's modifiable data store interface.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Modifiable
{
	/**
	 * Create resource items in the data store.
	 *
	 * Returns the ID of the created item if the data store supports
	 * auto-incrementing fields. Returns a success boolean otherwise.
	 *
	 * @param string $resource
	 * @param array  $data
	 * @return int|bool
	 */
	public function create($resource, $data);

	/**
	 * Update resource items in the data store.
	 *
	 * Returns the number of updated items.
	 *
	 * @param string $resource
	 * @param array  $data
	 * @param array  $filter   [optional]
	 * @param int    $limit    [optional]
	 * @return int
	 */
	public function update($resource, $data, array $filter = array(), $limit = null);

	/**
	 * Delete resource items from the data store.
	 *
	 * Returns the number of deleted items.
	 *
	 * @param string $resource
	 * @param array  $filter   [optional]
	 * @param int    $limit    [optional]
	 * @return int
	 */
	public function delete($resource, array $filter = array(), $limit = null);

	/**
	 * Retrieve the error that occured with the last operation.
	 *
	 * Returns false if there was no error.
	 *
	 * @return string|bool
	 */
	public function error();
}
