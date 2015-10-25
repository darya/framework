# Darya Framework

[![Latest Stable Version](https://poser.pugx.org/darya/framework/version)](//packagist.org/packages/darya/framework)
[![Latest Unstable Version](https://poser.pugx.org/darya/framework/v/unstable)](//packagist.org/packages/darya/framework)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/darya/framework.svg?style=flat)](https://scrutinizer-ci.com/g/darya/framework/?branch=develop)

Darya is a PHP framework for web application development.

Its components include:

- [Service container](#services)
- [HTTP abstractions](#http-abstractions)
- [Router](#routing)
- [Event dispatcher](#events)
- [ORM](#orm)
- [Views](#views)

This document covers the basics of using different components.

## Installation

Use [composer](https://getcomposer.org) to install the package `darya/framework`.

Otherwise just clone this repository into a directory such as
`/vendor/darya/framework`.

After this, you'll want to make use of a class autoloader to save you from
having to manually include every class. You can use composer's autoloader or
the autoloader that Darya provides.

### Composer's autoloader
```php
require_once 'vendor/autoload.php';
```

### Darya's autoloader

Darya's `autoloader.php` includes Composer's `autoload.php` if it exists.

```php
require_once 'vendor/darya/framework/autoloader.php';
```

## Basic usage

### Services

Darya's service container can be used to manage and resolve dependencies within
an application.

#### Resolving dependencies automatically

Out of the box, the container can be used to invoke callables or instantiate
classes with their concrete type-hinted dependencies automatically resolved.

##### Invoking callables

```php
use Darya\Service\Container;

class Foo
{
	public $bar;
	
	public function __construct(Bar $bar) {
		$this->bar = $bar;
	}
}

class Bar
{
	public $baz;
	
	public function __construct(Baz $baz) {
		$this->baz = $baz;
	}
}

class Baz {}

$container = new Container;

$closure = function (Foo $foo) {
	return $foo;
};

$foo = $container->call($closure);

$foo instanceof Foo;           // true
$foo->bar instanceof Bar;      // true
$foo->bar->baz instanceof Baz; // true
```

##### Instantiating classes

```php
$foo = $container->create('Foo');

$foo instanceof Foo;           // true
$foo->bar instanceof Bar;      // true
$foo->bar->baz instanceof Baz; // true
```

#### Registering services and aliases

Services can be values, objects, or closures.

- Values can be interface or class names to be resolved by the container
- Objects can be used as predefined services
- Closures can be used to define and compose a service manually

You can optionally define aliases for these services after the service
definitions themselves, or with a separate method call.

```php
$container = new Container;

// Register an object as a service
$container->set('App\SomeInterface', new App\SomeImplementation);

// Define an alias
$container->alias('some', 'App\SomeInterface');

// Register multiple services and aliases
$container->register(array(
	'App\SomeInterface'    => new App\SomeImplementation,
	'App\AnotherInterface' => function (Container $services) {
		return new App\AnotherImplementation($services->some);
	},
	'some'    => 'App\SomeInterface',
	'another' => 'App\AnotherInterface'
));
```

By default, closures are treated as instance definitions instead of factories.
This means the closure is executed once, when the service is first resolved,
and its return value is retained for subsequent resolutions.

#### Resolving services

```php
// Resolve services by class or interface
$container->resolve('App\SomeInterface');    // App/SomeImplementation
$container->resolve('App\AnotherInterface'); // App/AnotherImplementation

// Resolve services by alias
$container->resolve('some');    // App\SomeImplementation
$container->resolve('another'); // App\AnotherImplementation

// Shorter syntax
$container->some;    // App\SomeImplementation
$container->another; // App\AnotherImplementation

// Fetch services as they were registered
$container->get('some');     // App\SomeImplementation
$container->get('another');  // Closure

// Closures become lazy-loaded instances
$container->another === $container->another; // true
```

### HTTP abstractions

#### Requests

```php
use Darya\Http\Request;

$request = Request::createFromGlobals();

$username = $request->get('username');
$password = $request->post('password');
$uploaded = $request->file('uploaded');
$session  = $request->cookie('PHPSESSID');
$uri      = $request->server('PATH_INFO');
$ua       = $request->header('User-Agent');
```

#### Responses

##### Status and Content

```php
use Darya\Http\Response;

$response = new Response;

$response->status(200);
$response->content('Hello world!');
$response->send(); // Outputs 'Hello world!'

$response->status(404);
$response->content('Whoops!');
$response->send();
```

##### Redirection

```php
$response->redirect('http://google.co.uk/');
$response->send();
```

##### Cookies

```php
$response->cookies->set('key', 'value', '+1 day');

$cookie     = $response->cookies->get('key'); // 'value'
$expiration = $response->cookies->get('key', 'expire'); // strtotime('+1 day')

$response->cookies->delete('key');
```

#### Sessions

Sessions are planned to utilize a SessionHandlerInterface implementor.
For the time being, superglobals are hardcoded.

```php
use Darya\Http\Session;

$session = new Session;
$session->start();

$session->has('key'); // false

$session->set('key', 'value');
$session->has('key'); // true
$session->get('key'); // 'value'

// Alternative syntax
$session->key;   // 'another value';
$session['key']; // 'yet another value';

$session->delete('key');
$session->has('key'); // false
```

##### Request sessions

```php
$session = new Session;
$session->key = 'value';
$request = Request::createFromGlobals($session);

$request->session->key;   // 'value'
$request->session['key']; // 'value'
$request->session('key'); // 'value'
```

### Routing

Darya's router matches HTTP requests to a defined set of routes and can invoke
PHP callables based on what is matched.

#### Route matching

```php
use Darya\Routing\Router;

$router = new Router(array(
	'/' => function() {
		return 'Hello world!';
	}
));

/**
 * @var \Darya\Routing\Route
 */
$route = $router->match('/'); // $route->action === function() {return 'Hello world!';}

/**
 * @var \Darya\Http\Response
 */
$response = $router->dispatch('/'); // $response->content() === 'Hello world!'

$router->respond('/'); // Outputs 'Hello world!'
```

#### Route path parameters

##### Required parameters

```php
$router->add('/about/:what', function($what) {
	return "About $what!";
});

$router->respond('/about'); // Doesn't match

$router->respond('/about/me'); // Displays 'About me!'
```

##### Optional parameters

```php
$router->add('/about/:what?', function($what = 'nothing') {
	return "About $what!";
});

$router->respond('/about'); // Displays 'About nothing!'

$router->respond('/about/me'); // Displays 'About me!'
```

##### Using `:params` for arbitrary trailing parameters

```php
$router->add('/about/:params', function() {
	return implode(', ', func_get_args());
});

$router->respond('/about/One/two/three'); // Outputs 'One, two, three'
```

### Events

#### Listening to and dispatching events

```php
use Darya\Events\Dispatcher;

$dispatcher = new Dispatcher;

$dispatcher->listen('some_event', function ($thing) {
	return "one $thing";
});

$dispatcher->listen('some_event', function ($thing) {
	return "two $thing" . 's';
});

$results = $dispatcher->dispatch('some_event', 'thing'); // array('one thing', 'two things');
```

### ORM

#### Models

Darya models are self-validating objects used to represent business entities
within an application.

Darya's abstract `Model` implementation implements `ArrayAccess`, `Countable`,
`IteratorAggregate` and `Serializable`. It is essentially a flexible set of
data intended to represent one instance of a business entity.

##### Creating a model

```php
use Darya\Mvc\Model;

// Define a model
class Something extends Model {
	
}

// Instantiate it with some data
$something = new Something(array(
	'id'   => 72,
	'name' => 'Something',
	'type' => 'A thing'
));

// Access its properties using any convenient syntax
$id   = $something->id;          // 72
$name = $something['name'];      // 'Something'
$type = $something->get('type'); // 'A thing'
```

##### Iterating over a model

```php
$attributes = array();

foreach ($something as $key => $value) {
	$attributes[$key] => $value;
}
```

##### Serializing a model

```php
$serialized = serialize($something);
$attributes = $something->toArray();
$json       = $something->toJson();
```

##### Defining attribute types

```php
class Something extends Model {
	protected $attributes = array(
		'count' => 'int',
		'data'  => 'json'
	);
}

$something = new Something;

$something->count = '1';
$count = $something->count; // 1

$something->data = array('my' => 'data'); // Stored as '{"my":"data"}'
```

#### Records

Records are supercharged models with access to the database. They implement
the active record pattern, but with testability in mind. The database connection
for a single record instance, or all instances of a specific type of record, can
be swapped out for a different storage adapter, meaning they can easily tested
with mocks.

They use the typical convention of a singular class name mapping to a plural
database table name.

```php
use Darya\ORM\Record;

class User extends Record {

}
```

`User` would map to the **users** table. This can of course be overriden.

```php
class User extends Record {
	protected $table = 'people';
}
```

Records provide methods that you may be familiar with.

```php
// Change a single user
$user = User::find(1);
$user->name = 'Chris';
$user->save();

// Load all users
$users = User::all();
```

And some you may not have seen before.

```php
// Load all of the values of a given attribute
$list = User::listing('name');

// Load all of the distinct values of a given attribute
$names = User::distinct('name');
```

##### Relationships

Defining and working with relationships is a breeze.

```php
class Page extends Record {
	protected $relations = [
		'author'   => ['belongs_to', 'User', 'author_id'],
		'parent'   => ['belongs_to', 'Page', 'parent_id'],
		'children' => ['has_many',   'Page', 'parent_id'],
		'sections' => ['has_many',   'Section']
	];
}

$page = Page::find(1);

$children = $page->children;

foreach ($children as $child) {
	$child->title = "$page->title - $child->title";
}

$page->children = $children;

$page->save();
```

### Views

Views are used to separate an application's logic from its presentation. It's
good practice to treat them only as a means of displaying the data they are
given.

#### PHP view

The simple `Darya\View\Php` class is provided for you to easily use PHP as a
templating engine. Adapters are planned for popular templating engines,
including Smarty, Blade, Twig and Mustache.

#### views/index.php

```php
<p>Hello <?=$thing?>, this is a <?=$test?>.</p>

<?php foreach ($somethings as $something): ?>
	<p><?=ucfirst($something)?> something.</p>
<?php endforeach; ?>
```

#### index.php

```php
use Darya\View\Php;

$view = new Php('views/index.php');

$view->assign(array(
	'thing' => 'world',
	'test'  => 'test',
	'somethings' => array('one', 'two', 'three')
));

echo $view->render();
```

#### Output

```html
<p>Hello world, this is a test.</p>

	<p>One something.</p>
	<p>Two something.</p>
	<p>Three something.</p>
```
