<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Record;
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
	
	/**
	 * Replace a related model with the same ID.
	 * 
	 * If the related model does not have an ID or it is not found, it is simply
	 * appended.
	 * 
	 * @param \Darya\ORM\Record $instance
	 */
	protected function replace(Record $instance) {
		if (!$instance->id()) {
			return $this->related[] = $instance;
		}
		
		$replace = null;
		
		foreach ($this->related as $key => $related) {
			if ($related->id() === $instance->id()) {
				$replace = $key;
				
				break;
			}
		}
		
		if ($replace === null) {
			return $this->related[] = $instance;
		}
		
		$this->related[$replace] = $instance;
	}
	
	/**
	 * Associate the given models.
	 * 
	 * @param \Darya\ORM\Record[]|\Darya\ORM\Record $instances
	 */
	public function associate($instances) {
		foreach ((array) $instances as $instance) {
			$this->verify($instance);
			$this->replace($instance);
		}
		
		$this->save();
	}
	
	/**
	 * Dissociate the given models.
	 * 
	 * If no models are given, all related models are dissociated.
	 * 
	 * @param \Darya\ORM\Record[]|\Darya\ORM\Record $instances [optional]
	 */
	public function dissociate($instances = null) {
		$ids = array();
		
		$instances = $instances ?: $this->retrieve();
		
		foreach ((array) $instances as $instance) {
			$this->verify($instance);
			$instance->set($this->foreignKey, 0);
			$instance->save();
			$ids[] = $instance->id();
		}
		
		$keys = array();
		
		foreach ($this->related as $key => $instance) {
			if (!in_array($instance->id(), $ids)) {
				$keys[] = $key;
			}
		}
		
		$this->related = array_intersect_key($this->related, array_flip($keys));
	}
	
}
