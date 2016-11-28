<?php
namespace Darya\Tests\Unit\Service\Fixtures;

use Darya\Tests\Unit\Service\Fixtures\SomethingElse;

class AnotherThing
{
	public $else;
	
	public function __construct(SomethingElse $else)
	{
		$this->else = $else;
	}
}
