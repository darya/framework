<?php
namespace Darya\Database\Storage;

use Darya\Database\Result as DatabaseResult;
use Darya\Storage\Result as StorageResult;

/**
 * Storage result specific to working with database storage.
 * 
 * @todo This could be used to replace Darya\Database\Result
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Result extends StorageResult {
	
	/**
	 * Create a new database storage result from the given database
	 * connection result.
	 * 
	 * @param DatabaseResult $result
	 * @return StorageResult
	 */
	public static function createFromDatabaseResult(DatabaseResult $result) {
		
	}
	
	
	
}
