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
	 * 
	 * Optionally only save models with the given IDs.
	 * 
	 * @param array $ids [optional]
	 * @return int
	 */
	public function save($ids = array()) {
		$successful = 0;
		
		foreach ($this->related as $model) {
			if (!$ids || in_array($model->id(), $ids)) {
				$model->set($this->foreignKey, $this->parent->id());
				$successful += $model->save();
			}
		}
		
		return $successful;
	}
	
	/**
	 * Associate the given model.
	 * 
	 * Returns true if the model was successfully associated.
	 * 
	 * @param \Darya\ORM\Record $instance
	 * @return bool
	 */
	public function associate($instance) {
		$this->verify($instance);
		$this->related = array($instance);
		
		return !!$this->save();
	}
	
	/**
	 * Dissociate the related model.
	 * 
	 * Returns true if the model was successfully dissociated.
	 * 
	 * @return bool
	 */
	public function dissociate() {
		$associated = $this->retrieve();
		$associated->set($this->foreignKey, 0);
		
		return $associated->save();
	}
	
}
