<?php
namespace Darya\Tests\Unit\ORM\Fixtures;

use Darya\ORM\Record;

use Darya\Tests\Unit\ORM\Fixtures\User;

class Role extends Record
{
	protected $relations = array(
		'users' => ['belongs_to_many', User::class, null, null, 'user_roles']
	);
}
