<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Relation;

/**
 * Darya's has-many entity relation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class HasMany extends Has {
	
	/**
	 * Retrieve the related models.
	 * 
	 * @return Record[]
	 */
	public function retrieve() {
		return $this->all();
	}
	
}
