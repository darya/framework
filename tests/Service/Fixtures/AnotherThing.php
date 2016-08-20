<?php
namespace Darya\Tests\Service\Fixtures;

use Darya\Tests\Service\Fixtures\SomethingElse;

class AnotherThing
{
	public $else;
	
	public function __construct(SomethingElse $else)
	{
		$this->else = $else;
	}
}
