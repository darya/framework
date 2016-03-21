<?php
namespace Darya\Database\Query;

use Darya\Storage;

/**
 * Interface for translating storage queries to database (SQL) queries.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Translator {
	
	/**
	 * Translate the given storage query to a database-specific query.
	 * 
	 * @param Storage\Query $storageQuery
	 * @return \Darya\Database\Query
	 */
	public function translate(Storage\Query $storageQuery);
	
}
