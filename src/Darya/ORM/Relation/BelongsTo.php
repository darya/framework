<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Relation;

/**
 * Darya's belongs-to entity relation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class BelongsTo extends Relation {
	
	/**
	 * Set the default keys for the relation if they have not yet been set.
	 */
	protected function setDefaultKeys() {
		if (!$this->foreignKey) {
			$this->foreignKey = $this->prepareForeignKey(get_class($this->target));
		}
		
		$this->localKey = $this->target->key();
	}
	
	/**
	 * Retrieve the filter for this relation.
	 * 
	 * @return array
	 */
	public function filter() {
		return array($this->target->key() => $this->parent->get($this->foreignKey));
	}
	
	/**
	 * Retrieve the related model.
	 * 
	 * @return \Darya\ORM\Record
	 */
	public function retrieve() {
		if ($this->parent->get($this->foreignKey)) {
			return $this->one();
		}
	}
	
	/**
	 * Associate the given model.
	 * 
	 * @param \Darya\ORM\Record $instance
	 */
	public function associate(Record $instance) {
		$this->verify($instance);
		$this->parent->set($this->foreignKey, $instance->id());
		$this->parent->save();
	}
	
	/**
	 * Dissociate the related model.
	 */
	public function dissociate() {
		$this->parent->set($this->foreignKey, 0);
		$this->parent->save();
	}
	
}
