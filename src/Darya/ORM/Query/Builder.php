<?php

namespace Darya\ORM\Query;

use Darya\ORM;
use Darya\Storage;

/**
 * Darya's ORM query builder.
 *
 * @mixin ORM\Query
 * @author Chris Andrew <chris@hexus.io>
 */
class Builder extends Storage\Query\Builder
{
	/**
	 * Create a new ORM query builder.
	 *
	 * @param ORM\Query         $query
	 * @param Storage\Queryable $storage
	 */
	public function __construct(ORM\Query $query, Storage\Queryable $storage)
	{
		parent::__construct($query, $storage);
	}
}
