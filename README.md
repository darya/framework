# Darya Framework

[![Latest Darya Release](https://img.shields.io/github/release/hexusio/darya-framework.svg?style=flat "Latest Darya Release")](https://github.com/hexusio/darya-framework/tree/develop)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/hexusio/darya-framework/develop.svg?style=flat)](https://scrutinizer-ci.com/g/hexusio/darya-framework/?branch=develop)

Darya is a PHP framework for web application development.

Its components include:

- [Autoloader](#autoloading)
- [Service container](#services)
- [HTTP abstractions](#http-abstractions)
- [Router](#routing)
- [Event dispatcher](#events)
- [MVC foundation](#model-view-controller-foundation)

The framework has been extracted from and is intended as a foundation for the Darya CMS project.

Inspired by PHP frameworks such as Laravel, Phalcon and Symfony.

This document covers the basics of using the components. If you'd like more detail, please delve into the the relevant directory for component-specific read-me documents.

## Installation

Use [composer](https://getcomposer.org) to install the package `darya/framework`.

Otherwise just clone this repository into a directory such as `/vendor/darya/framework`.

## Introduction

### Autoloading

To get started you'll want to make use of a class autoloader to save you from
having to manually `include` every class you want to use.

#### Composer's autoloader
```php
require_once 'vendor/autoload.php';
```

#### Darya's autoloader

Darya's `autoloader.php` includes Composer's `autoload.php`.

```php
require_once 'vendor/darya/framework/autoloader.php';
```

You can optionally configure Darya's autoloader by providing namespace mappings.

```php
$autoloader = require_once 'vendor/darya/framework/autoloader.php';

$autoloader->namespaces(array(
	'MyNamespace'         => 'app',
	'MyNamespace'         => 'app/MyNamespace',
	'MyNamespace\MyClass' => 'app/MyNamespace/MyClass.php'
));
```

### Services

Darya's service container can be used to manage dependencies within an
application.

#### Registering services and aliases

Services can be values, instances (objects), or closures that define how an
object is instantiated.

You can optionally define aliases for these services after the service
definitions themselves.

```php
use Darya\Service\Container;

$container = new Container;

$container->register(array(
	'App\SomeInterface'    => new App\SomeImplementation,
	'App\AnotherInterface' => function (Container $services) {
		return new App\AnotherImplementation($services->some);
	},
	'some'    => 'App\SomeInterface',
	'another' => 'App\AnotherInterface'
));
```

#### Resolving services

```php
// Fetch services as they were registered
$container->get('some');     // App\SomeImplementation
$container->get('another');  // Closure

// Resolve services
$container->resolve('some');    // App\SomeImplementation
$container->resolve('another'); // App\AnotherImplementation

// Shorter syntax
$container->some;    // App\SomeImplementation
$container->another; // App\AnotherImplementation

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

##### 200 OK

```php
use Darya\Http\Response;

$response = new Response;

$response->status(200);
$response->content('Hello world!');
$response->send(); // Outputs 'Hello world!'
```

##### 404 Not Found

```php
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
$response->setCookie('key', 'value', strtotime('+1 day', time()));
$cookie = $response->getCookie('key'); // 'value'

$response->deleteCookie('key');
```

#### Sessions

Sessions will eventually accept a SessionHandlerInterface implementor as a
constructor argument. Superglobals are currently hardcoded.

```php
use Darya\Http\Session;

$session = new Session;
$session->start();

$session->set('key', 'value');
$session->has('key'); // true
$session->get('key'); // 'value'

// Alternative syntax
$session->key;   // 'another value';
$session['key']; // 'yet another value';

$session->delete('key');
$session->has('key'); // false;
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

Darya's router is the heart of the framework. It matches HTTP requests to routes
and can invoke PHP callables based on the match.

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

### Model-View-Controller Foundation

#### Models

Darya models are self-validating value objects used to represent entities within
an application.

Darya's abstract `Model` implementation implements `ArrayAccess`, `Countable`,
`IteratorAggregate` and `Serializable`. It is essentially a flexible collection
of data.

##### Creating a model

Model attribute keys are currently prefixed with the class name and an
underscore (`classname_`) by default. All attribute keys are treated
case-insensitively

This prefix does not need to be used when accessing attributes; only when
setting data. To prevent the use of a prefix simply set the
`protected $fieldPrefix = '';` property on your model.

```php
use Darya\Mvc\Model;

class Something extends Model {
	
}

$something = new Something(array(
	'something_id'   => 72,
	'something_name' => 'Something',
	'something_type' => 'A thing'
));

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
$json = $something->toJson();
```

#### Views

To be documented.

#### Controllers

To be documented.