<?php

namespace Darya\ORM\Relation;

use Darya\ORM\Record;
use Exception;

/**
 * Darya's has-many entity relation.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class HasMany extends Has
{
	/**
	 * Eagerly load the related models of the given parent instances.
	 *
	 * Retrieves the related models without matching them to their parents.
	 *
	 * @param array $instances
	 * @return array
	 * @throws Exception
	 */
	public function eagerLoad(array $instances)
	{
		$this->verifyParents($instances);
		$ids = static::attributeList($instances, $this->parent->key());

		$filter = array_merge($this->filter(), [
			$this->foreignKey => array_unique($ids)
		]);

		$data = $this->storage()->read($this->target->table(), $filter, $this->order());

		return $this->target->generate($data);
	}

	/**
	 * Eagerly load and match the related models for the given parent instances.
	 *
	 * Returns the given instances with their related models loaded.
	 *
	 * @param array $instances
	 * @return array
	 * @throws Exception
	 */
	public function eager(array $instances)
	{
		if ($this->parent instanceof $this->target) {
			return $this->eagerSelf($instances);
		}

		$related = $this->eagerLoad($instances);

		$instances = $this->match($instances, $related);

		return $instances;
	}

	/**
	 * Eagerly load the related models from the same table.
	 *
	 * This continues to load the same relation recursively.
	 *
	 * @param array $instances
	 * @return array
	 * @throws Exception
	 */
	protected function eagerSelf(array $instances)
	{
		$parents = $instances;

		while ($related = $this->eagerLoad($parents)) {
			$this->match($parents, $related);

			$parents = $related;
		}

		$this->match($parents, []);

		return $instances;
	}

	/**
	 * Match the given related models to their parent instances.
	 *
	 * @param Record[] $instances
	 * @param Record[] $related
	 * @return Record[]
	 */
	protected function match(array $instances, array $related)
	{
		$list = $this->adjacencyList($related);

		foreach ($instances as $instance) {
			$key   = $instance->id();
			$value = isset($list[$key]) ? $list[$key] : [];
			$instance->relation($this->name)->set($value);
		}

		return $instances;
	}

	/**
	 * Associate the given models.
	 *
	 * Loads any currently associated models before attaching and saving
	 * the given models.
	 *
	 * Returns the number of successfully associated models.
	 *
	 * @param Record[]|Record $instances
	 * @return int
	 * @throws Exception
	 */
	public function associate($instances)
	{
		$this->verify($instances);

		$this->load();

		$this->attach($instances);

		$ids = static::attributeList($instances, $this->target->key());

		$successful = 0;

		foreach ($this->related as $model) {
			if (!$ids || !$model->id() || in_array($model->id(), $ids)) {
				$model->set($this->foreignKey, $this->parent->id());
				$successful += $model->save();

				continue;
			}

			$this->persist($model);
		}

		return (int) $successful;
	}

	/**
	 * Retrieve the related models.
	 *
	 * @return Record[]
	 */
	public function retrieve()
	{
		return $this->all();
	}

	/**
	 * Dissociate all currently associated models.
	 *
	 * Returns the number of models successfully dissociated.
	 *
	 * TODO: Consider constraints
	 *
	 * @return int
	 */
	public function purge()
	{
		$this->related = [];

		return (int) $this->storage()->query($this->target->table())
			->where($this->foreignKey, $this->parent->get($this->localKey))
			->update([
				$this->foreignKey => 0
			])
			->cheers()->affected;
	}
}
