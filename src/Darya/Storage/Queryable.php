<?php
namespace Darya\Storage;

use Darya\Storage\Query;

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
	 * @return \Darya\Storage\Result
	 */
	public function execute(Query $query);
	
	/**
	 * Open a query on the given resource.
	 * 
	 * @param string       $resource
	 * @param array|string $fields   [optional]
	 * @return \Darya\Storage\Query\Builder
	 */
	public function query($resource, $fields = array());
	
}
