<?php
namespace Darya\Database;

use Darya\Storage\Error as StorageError;

/**
 * Darya's database error representation.
 * 
 * @property int    $number
 * @property string $message
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Error extends StorageError
{
	
}
