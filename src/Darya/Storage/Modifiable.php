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
	 * Create resource instances in the data store.
	 * 
	 * @param string $resource
	 * @param array  $data
	 */
	public function create($resource, $data);
	
	/**
	 * Update resource instances in the data store.
	 * 
	 * Returns the number of updated rows, or false if there was an error.
	 * 
	 * @param string $resource
	 * @param array  $data
	 * @param array  $filter   [optional]
	 * @param int    $limit    [optional]
	 * @return int|bool
	 */
	public function update($resource, $data, array $filter = array(), $limit = null);
	
	/**
	 * Delete resource instances from the data store.
	 * 
	 * Returns the number of deleted rows, or false if there was an error.
	 * 
	 * @param string $resource
	 * @param array  $filter   [optional]
	 * @param int    $limit    [optional]
	 * @return int|bool
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
