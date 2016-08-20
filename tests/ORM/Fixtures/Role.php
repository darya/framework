<?php
namespace Darya\Tests\ORM\Fixtures;

use Darya\ORM\Record;

use Darya\Tests\ORM\Fixtures\User;

class Role extends Record
{
	protected $relations = array(
		'users' => ['belongs_to_many', User::class, null, null, 'user_roles']
	);
}
