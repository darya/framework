<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Record;
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
	 * Replace the in-memory related model with the given instance.
	 * 
	 * @param \Darya\ORM\Record $instance
	 */
	protected function replace(Record $instance) {
		$this->related = array($instance);
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
	 * @return bool
	 */
	public function associate(Record $instance) {
		$this->verify($instance);
		$this->parent->set($this->foreignKey, $instance->id());
		
		return $this->parent->save();
	}
	
	/**
	 * Dissociate the related model.
	 * 
	 * @return bool
	 */
	public function dissociate() {
		$this->parent->set($this->foreignKey, 0);
		
		return $this->parent->save();
	}
	
}
