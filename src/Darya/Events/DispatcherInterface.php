<?php
namespace Darya\Events;

use Darya\Events\DispatchableInterface;
use Darya\Events\ListenableInterface;
use Darya\Events\SubscribableInterface;

/**
 * Darya's unioned event dispatcher interface.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface DispatcherInterface extends DispatchableInterface, ListenableInterface, SubscribableInterface {
	
}
