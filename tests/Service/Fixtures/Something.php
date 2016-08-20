<?php
namespace Darya\Tests\Service\Fixtures;

use Darya\Tests\Service\Fixtures\AnotherThing;
use Darya\Tests\Service\Fixtures\OtherInterface;
use Darya\Tests\Service\Fixtures\SomeInterface;
use Darya\Tests\Service\Fixtures\SomethingElse;

class Something implements SomeInterface, OtherInterface
{
	public $another;
	
	public $else;
	
	public function __construct(AnotherThing $another, SomethingElse $else)
	{
		$this->another = $another;
		$this->else = $else;
	}
}
