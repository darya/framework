<?php
namespace Darya\Database\Query\Translator;

use Darya\Database;
use Darya\Database\Query\AbstractSqlTranslator;
use Darya\Storage;

/**
 * Darya's SQL Server query translator.
 * 
 * TODO: Offset!
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class SqlServer extends AbstractSqlTranslator {
	
	/**
	 * Prepare a LIMIT clause using the given limit and offset.
	 * 
	 * @param int $limit  [optional]
	 * @param int $offset [optional]
	 * @return string
	 */
	protected function prepareLimit($limit = null, $offset = 0) {
		if (!is_numeric($limit) || !is_numeric($offset)) {
			return null;
		}
		
		$limit = (int) $limit;
		$offset = (int) $offset;
		
		return "TOP $limit";
	}
	
}
