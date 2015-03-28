<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Relation;

class HasMany extends Has {
	
	public function retrieve() {
		if (!$this->related) {
			$this->related = $this->all();
		}
		
		return $this->related;
	}
	
}
