<?php
namespace Darya\Tests\Unit\Service\Fixtures;

use Darya\Tests\Unit\Service\Fixtures\AnotherThing;
use Darya\Tests\Unit\Service\Fixtures\OtherInterface;
use Darya\Tests\Unit\Service\Fixtures\SomeInterface;
use Darya\Tests\Unit\Service\Fixtures\SomethingElse;

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
