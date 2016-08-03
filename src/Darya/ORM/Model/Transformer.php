<?php
namespace Darya\ORM\Model;

/**
 * Interface for transforming model attributes given a type and value.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface Transformer
{
	/**
	 * Transform the given value depending on the given type.
	 * 
	 * Unrecognised types simply return the value as is.
	 * 
	 * @param string $value
	 * @param string $type  [optional]
	 */
	public function transform($value, $type = '');
}
