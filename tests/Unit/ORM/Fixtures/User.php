<?php
namespace Darya\Tests\Unit\ORM\Fixtures;

use Darya\ORM\Record;
use Darya\ORM\Relation;

use Darya\Tests\Unit\ORM\Fixtures\Post;
use Darya\Tests\Unit\ORM\Fixtures\Role;

/**
 * @method Relation\Has           padawan()
 * @method Relation\BelongsTo     manager()
 * @method Relation\BelongsTo     master()
 * @method Relation\HasMany       posts()
 * @method Relation\BelongsToMany roles()
 */
class User extends Record
{
	protected $relations = array(
		'padawan' => ['has',             User::class, 'master_id'],
		'manager' => ['belongs_to',      User::class, 'manager_id'],
		'master'  => ['belongs_to',      User::class, 'master_id'],
		'posts'   => ['has_many',        Post::class, 'author_id'],
		'roles'   => ['belongs_to_many', Role::class, null, null, 'user_roles']
	);
	
	protected $search = array(
		'firstname', 'surname'
	);
}
