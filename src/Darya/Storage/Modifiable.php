<?php
namespace Darya\Storage;

/**
 * Darya's modifiable data store interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Modifiable {
	
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
	 * @param string $resource
	 * @param array  $data
	 */
	public function update($resource, $data, array $filter = array(), $limit = null);
	
	/**
	 * Delete resource instances from the data store.
	 * 
	 * @param string $resource
	 * @param array  $data
	 */
	public function delete($resource, array $filter = array(), $limit = null);
	
	
	/**
	 * Retrieve any errors that occured during the last modification attempt.
	 * 
	 * @return array
	 */
	public function errors();
	
}
