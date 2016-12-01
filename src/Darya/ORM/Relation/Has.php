<?php
namespace Darya\ORM\Relation;

use Exception;
use Darya\ORM\Record;
use Darya\ORM\Relation;

/**
 * Darya's has-one entity relation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Has extends Relation
{
	/**
	 * Set the default keys for the relation if they have not yet been set.
	 */
	protected function setDefaultKeys()
	{
		if (!$this->foreignKey) {
			$this->foreignKey = $this->prepareForeignKey(get_class($this->parent));
		}
		
		$this->localKey = $this->parent->key();
	}
	
	/**
	 * Eagerly load the related models for the given parent instances.
	 * 
	 * Returns the given instances with their related models loaded.
	 * 
	 * @param array $instances
	 * @return array
	 */
	public function eager(array $instances)
	{
		$this->verifyParents($instances);
		$ids = static::attributeList($instances, 'id');
		
		$filter = array_merge($this->filter(), array(
			$this->foreignKey => array_unique($ids)
		));
		
		$data = $this->storage()->read($this->target->table(), $filter, $this->order());
		
		$class = get_class($this->target);
		$generated = $class::generate($data);
		
		$related = array();
		
		foreach ($generated as $model) {
			$related[$model->get($this->foreignKey)] = $model;
		}
		
		foreach ($instances as $instance) {
			$key = $instance->id();
			$value = isset($related[$key]) ? $related[$key] : array();
			$instance->relation($this->name)->set($value);
		}
		
		return $instances;
	}
	
	/**
	 * Retrieve the related model.
	 * 
	 * @return Record|null
	 */
	public function retrieve()
	{
		return $this->one();
	}
	
	/**
	 * Associate the given model.
	 * 
	 * Dissociates any currently associated model beforehand.
	 * 
	 * Returns the number of successfully associated models.
	 * 
	 * @param Record[]|Record $instances
	 * @return int
	 */
	public function associate($instances)
	{
		$this->verify($instances);
		
		$this->dissociate();
		$this->attach($instances);
		
		$ids = static::attributeList($instances, 'id');
		
		$successful = 0;
		
		foreach ($this->related as $model) {
			$this->persist($model);
			
			if (!$ids || in_array($model->id(), $ids)) {
				$model->set($this->foreignKey, $this->parent->id());
				$successful += $model->save();
			}
		}
		
		return (int) $successful;
	}
	
	/**
	 * Dissociate the related model.
	 * 
	 * Returns true if the model was successfully dissociated.
	 * 
	 * @param Record[]|Record $instances [optional]
	 * @return int
	 */
	public function dissociate($instances = array())
	{
		$this->verify($instances);
		
		// Fall back to loading existing relations if none are given
		$associated = static::arrayify($instances) ?: $this->load(1);
		
		// Mark these models as detached
		$this->detach($associated);
		
		$successful = 0;
		
		// Persist the detachment of the models
		foreach ($this->detached as $model) {
			$model->set($this->foreignKey, 0);
			$successful += $model->save();
		}
		
		// Clear the set of models to detach
		$this->detached = array();
		
		return $successful;
	}
}
