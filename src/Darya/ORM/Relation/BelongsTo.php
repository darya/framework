<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Relation;

class BelongsTo extends Relation {
	
	protected function setDefaultKeys() {
		if (!$this->foreignKey) {
			$this->foreignKey = $this->prepareForeignKey(get_class($this->target));
		}
		
		$this->localKey = $this->target->key();
	}
	
	public function filter() {
		return array($this->target->key() => $this->parent->get($this->foreignKey));
	}
	
	public function retrieve() {
		return $this->one();
	}
	
}
