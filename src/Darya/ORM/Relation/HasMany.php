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
	 * Eagerly load the related models for the given parent instances.
	 * 
	 * Returns the given instances with their related models loaded.
	 * 
	 * @param array $instances
	 * @param string $name TODO: Remove this and store as a property
	 * @return array
	 */
	public function eager(array $instances, $name) {
		return $instances;
	}
	
	/**
	 * Retrieve the related models.
	 * 
	 * @return Record[]
	 */
	public function retrieve() {
		return $this->all();
	}
	
	/**
	 * Associate the given models.
	 * 
	 * Returns the number of models successfully associated.
	 * 
	 * @param \Darya\ORM\Record[]|\Darya\ORM\Record $instances
	 * @return int
	 */
	public function associate($instances) {
		$ids = array();
		
		foreach (static::arrayify($instances) as $instance) {
			$this->verify($instance);
			$this->replace($instance);
			$ids = $instance->id();
		}
		
		return $this->save($ids);
	}
	
	/**
	 * Dissociate the given models.
	 * 
	 * If no models are given, all related models are dissociated.
	 * 
	 * Returns the number of models successfully dissociated.
	 * 
	 * @param \Darya\ORM\Record[]|\Darya\ORM\Record $instances [optional]
	 * @return int
	 */
	public function dissociate($instances = null) {
		$ids = array();
		
		$instances = $instances ?: $this->retrieve();
		
		$successful = 0;
		
		foreach (static::arrayify($instances) as $instance) {
			$this->verify($instance);
			$instance->set($this->foreignKey, 0);
			
			if ($instance->save()) {
				$ids[] = $instance->id();
				$successful++;
			}
		}
		
		$this->reduce($ids);
		
		return $successful;
	}
	
	/**
	 * Dissociate all currently associated models.
	 * 
	 * Returns the number of models successfully dissociated.
	 * 
	 * @return int
	 */
	public function purge() {
		$successful = 0;
		
		foreach ($this->related as $instance) {
			$instance->set($this->foreignKey, 0);
			$successful += $instance->save();
		}
		
		return $successful;
	}
	
}
