<?php

namespace Darya\Storage;

/**
 * Darya's queryable data store interface.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface Queryable
{
	/**
	 * Run the given query.
	 *
	 * @param Query $query
	 * @return Result
	 */
	public function run(Query $query);

	/**
	 * Open a query on the given resource.
	 *
	 * @param string       $resource The resource to query.
	 * @param array|string $fields   [optional] The fields to retrieve.
	 * @return Query\Builder
	 */
	public function query($resource, $fields = []);
}
