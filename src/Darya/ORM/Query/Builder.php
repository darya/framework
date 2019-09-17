<?php

namespace Darya\ORM\Query;

use Darya\ORM\Query;
use Darya\Storage;

/**
 * Darya's ORM query builder.
 *
 * @mixin Query
 * @author Chris Andrew <chris@hexus.io>
 */
class Builder extends Storage\Query\Builder
{
	/**
	 * Create a new ORM query builder.
	 *
	 * @param Query             $query
	 * @param Storage\Queryable $storage
	 */
	public function __construct(Query $query, Storage\Queryable $storage)
	{
		parent::__construct($query, $storage);
	}
}
