<?php
namespace Darya\ORM\Model;

/**
 * A trait that can be used to implement the transformer interface in a way that
 * makes it easy to write methods that transform different model attribute
 * types.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
trait TransformerTrait {
	
	/**
	 * Transform the given value depending on the given type.
	 * 
	 * Unrecognised types simply return the value as is.
	 * 
	 * @param string $value
	 * @param string $type  [optional]
	 */
	public function transform($value, $type = '') {
		$method = 'transform' . ucfirst($type);
		
		if (method_exists($this, $method)) {
			return $this->$method($value);
		}
		
		return $value;
	}
	
}
