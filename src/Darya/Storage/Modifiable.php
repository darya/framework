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
	public function update($resource, $data);
	
	/**
	 * Delete resource instances from the data store.
	 * 
	 * @param string $resource
	 * @param array  $data
	 */
	public function delete($resource, $data);
	
}
