<?php
namespace Darya\Storage;

/**
 * Darya's queryable data store interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Queryable {
	
	/**
	 * Execute the given query.
	 * 
	 * @param Query $query
	 */
	public function execute(Query $query);
	
	/**
	 * Open a query on the given resource.
	 * 
	 * @param string $resource
	 */
	public function query($resource);
	
}
