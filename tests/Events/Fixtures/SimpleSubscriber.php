<?php
namespace Darya\Tests\Events\Fixtures;

use Darya\Events\Subscriber;

class SimpleSubscriber implements Subscriber
{
	/**
	 * @var int
	 */
	public $number;
	
	/**
	 * Retrieve the subscriptions.
	 * 
	 * Example:
	 *   return array(
	 *     'event.name'  => array($this, 'listener'),
	 *     'other.event' => function ($argument) {
	 *       return $argument;
	 *     }
	 *   );
	 * 
	 * @return array
	 */
	public function getEventSubscriptions()
	{
		return array(
			'some.event' => array($this, 'listener')
		);
	}
	
	/**
	 * Listen to the event.
	 * 
	 * @param int $number
	 */
	public function listener($number)
	{
		$this->number += $number;
	}
}
