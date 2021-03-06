<?php

namespace Darya\ORM\Relation;

use Darya\ORM\Record;
use Darya\ORM\Relation;
use Exception;

/**
 * Darya's belongs-to entity relation.
 *
 * TODO: Override attach and detach, setting the foreign key on the parent model.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class BelongsTo extends Relation
{
	/**
	 * Set the default keys for the relation if they have not yet been set.
	 */
	protected function setDefaultKeys()
	{
		if (!$this->foreignKey) {
			$this->foreignKey = $this->prepareForeignKey(get_class($this->target));
		}

		$this->localKey = $this->target->key();
	}

	/**
	 * Retrieve the default filter for this relation.
	 *
	 * @return array
	 */
	protected function defaultConstraint()
	{
		return [$this->localKey => $this->parent->get($this->foreignKey)];
	}

	/**
	 * Eagerly load the related models for the given parent instances.
	 *
	 * Returns the given instances with their related models loaded.
	 *
	 * @param array $instances
	 * @return array
	 * @throws Exception
	 */
	public function eager(array $instances)
	{
		$this->verifyParents($instances);
		$ids = static::attributeList($instances, $this->foreignKey);

		$filter = array_merge($this->filter(), [
			$this->localKey => array_unique($ids)
		]);

		$data = $this->storage()->read($this->target->table(), $filter, $this->order());

		$class     = get_class($this->target);
		$generated = $class::generate($data);

		$related = [];

		foreach ($generated as $model) {
			$related[$model->id()] = $model;
		}

		foreach ($instances as $instance) {
			$key   = $instance->get($this->foreignKey);
			$value = isset($related[$key]) ? [$related[$key]] : [];
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
	 * @param Record[]|Record $instances
	 * @return int
	 * @throws Exception
	 */
	public function associate($instances)
	{
		$this->attach($instances);
		$instances = static::arrayify($instances);

		if (empty($instances)) {
			return 0;
		}

		$instance = $instances[0];

		$instance->save();
		$this->set([$instance]);
		$this->parent->set($this->foreignKey, $instance->id());

		return (int) $this->parent->save([
			'skipRelations' => true
		]);
	}

	/**
	 * Dissociate the related model.
	 *
	 * @param Record[]|Record $instances [optional]
	 * @return int
	 * @throws Exception
	 */
	public function dissociate($instances = [])
	{
		$this->clear();
		$this->parent->set($this->foreignKey, 0);

		$saved = (int) $this->parent->save([
			'skipRelations' => true
		]);

		$this->loaded = false;

		return $saved;
	}
}
