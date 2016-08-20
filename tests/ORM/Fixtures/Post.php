<?php
namespace Darya\Tests\ORM\Fixtures;

use Darya\ORM\Record;

use Darya\Tests\ORM\Fixtures\User;

class Post extends Record
{
	protected $relations = array(
		'author' => ['belongs_to', User::class, 'author_id']
	);
}
