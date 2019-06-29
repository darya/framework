<?php
namespace Darya\Events\Contracts;

use Darya\Events\Dispatchable;

interface DispatcherAware
{
	/**
	 * Set the event dispatcher.
	 *
	 * @param Dispatchable $dispatcher
	 * @return void
	 */
	public function setEventDispatcher(Dispatchable $dispatcher);
}
