<?php
namespace Darya\Database\Query\Translator;

use Darya\Database;
use Darya\Database\Query\Translator;
use Darya\Storage;

/**
 * Darya's SQL Server query translator.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class SqlServer implements Translator {
	
	/**
	 * Translate the given storage query into an SQL Server query.
	 * 
	 * @param Storage\Query $storageQuery
	 * @return Database\Query
	 */
	public function translate(Storage\Query $storageQuery) {
		
	}
	
}
