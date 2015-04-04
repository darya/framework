<?php
namespace Darya\ORM\Relation;

use Exception;
use Darya\ORM\Relation;

/**
 * Darya's has-one entity relation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Has extends Relation {
	
	/**
	 * Set the default keys for the relation if they have not yet been set.
	 */
	public function setDefaultKeys() {
		if (!$this->foreignKey) {
			$this->foreignKey = $this->prepareForeignKey(get_class($this->parent));
		}
		
		$this->localKey = $this->parent->key();
	}
	
	/**
	 * Retrieve the related model.
	 * 
	 * @return Record
	 */
	public function retrieve() {
		return $this->one();
	}
	
	/**
	 * Save the related models.
	 */
	public function save() {
		foreach ($this->related as $model) {
			$model->set($this->foreignKey, $this->parent->id());
			$model->save();
		}
	}
	
	/**
	 * Associate the given model.
	 * 
	 * @param \Darya\ORM\Record $instance
	 */
	public function associate($instance) {
		$this->verify($instance);
		$this->related = array($instance);
		$this->save();
	}
	
	/**
	 * Dissociate the related model.
	 * 
	 * If no model is given, one will be loaded.
	 * 
	 * @param \Darya\ORM\Record $instance [optional]
	 */
	public function dissociate($instance = null) {
		if ($associated = $instance ?: $this->retrieve()) {
			$associated->set($this->foreignKey, 0);
			$associated->save();
		}
	}
	
}
