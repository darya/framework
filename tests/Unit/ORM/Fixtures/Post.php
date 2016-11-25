<?php
namespace Darya\Tests\Unit\ORM\Fixtures;

use Darya\ORM\Record;

use Darya\Tests\Unit\ORM\Fixtures\User;

class Post extends Record
{
	protected $relations = array(
		'author' => ['belongs_to', User::class, 'author_id']
	);
}
