# Darya Framework

Darya is a PHP framework for web application development.

Its components include:

- Autoloader
- HTTP abstractions
- Router & Dispatcher
- Service container
- MVC foundation

The framework has been extracted from and is intended as a foundation for the Darya CMS project.

Inspired by PHP frameworks such as Laravel, Phalcon and Symfony.

This document covers the basics of using the components. If you'd like more detail, please delve into the the relevant directory for component-specific read-me documents.

## Installation

Use [composer](https://getcomposer.org) to install the package `darya/framework`.

Otherwise just clone this repository into a directory such as `/vendor`.

## Basic usage

### Autoloading

To get started you'll want to make use of a class autoloader to save you from having to include a file for every class you want to use.

#### Composer's autoloader
```php
require_once 'vendor/autoload.php';
```

#### Darya's autoloader

Darya's `autoloader.php` include's Composer's `autoload.php`.

```php
require_once 'vendor/darya/framework/autoloader.php';
```

You can optionally configure Darya's autoloader.

```php
$autoloader = require_once 'vendor/darya/framework/autoloader.php';

$autoloader->registerNamespaces(array(
	'MyNamespace' => 'app',
	'MyNamespace' => 'app/MyNamespace',
	'MyNamespace\MyClass' => 'app/MyNamespace/MyClass.php'
));
```

### Routing

Darya's router is the heart of the framework. Its purpose is to match HTTP 
requests to routes and invoke functions or class methods based on the parameters
of matched routes. These parameters be set dynamically by the request path.

#### Front controller

First, you'll want to set up a PHP script as a [front controller](http://en.wikipedia.org/wiki/Front_Controller_pattern). If you're using Apache as your web server you could achieve this with a `.htaccess` file at the root of your public directory. 

```
RewriteEngine on

# Redirect requests for any non-existing files to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule .* index.php [L,QSA]
```

#### Defining and matching routes

You can define routes in an array when instantiating a router, where request 
paths are keys and route parameters are values.

Reserved route parameters are as follows:

- `namespace`  - The namespace to prepend to a matched controller
- `controller` - The class to use as a controller
- `action`     - The anonymous function or controller method to run when the 
route is dispatched
- `params`     - Slash-delimited parameters to pass to actions as arguments. 
This one is always optional and should be used at the end of a route's request path.

Here is an example of instantiating the router with an initial route definition.
The anonymous function becomes the route's `action` parameter.

```php
use Darya\Routing\Router;

$router = new Router(array(
	'/' => function() {
		return 'Hello world!';
	}
));
```

The route can then be matched:

```php
/**
 * @var Darya\Routing\Route 
 */
$route = $router->match('/'); // $route->action == 
```

dispatched (matched and invoked):

```php
$result = $router->dispatch('/'); // Contains 'Hello world!'
```

or responded to (matched, invoked and sent as an HTTP response):

```php
$router->respond('/'); // Displays 'Hello world!'
```

#### Route path parameters

Request path segments prefixed with a colon, such as `:action`, are interpreted
as route parameters while matching the request. Existing route parameters are
overwritten by this process.

Defining and utilising a required route path parameter:

```php
$router->add('/about/:what', function($what) {
	return "About $what!";
});

$router->respond('/about/me'); // Displays 'About me!'
```

Defining and utilising an optional route path parameter:

```php
$router->add('/about/:what?', function($what = 'nothing') {
	return "About $what!";
});

$router->respond('/about'); // Displays 'About nothing!'

$router->respond('/about/me'); // Displays 'About me!'
```

Utilising the special `:params` parameter:

```php
$router->add('/about/:params', function() {
	return implode(', ', func_get_args());
});

$router->respond('/about/One/two/three'); // Displays 'One, two, three'
```

#### Setting an error handler

A router's error handler is invoked when no route was matched when attempting
to dispatch a request.

```php
use Darya\Http\Response;

// Set an error handler for requests that don't match a route
$router->setErrorHandler(function(){
	$response = new Response('No route was matched!');
	$response->setStatus(404);
	return $response;
});
```

The callback can optionally accept a `$response` argument.

### Controllers

Darya's abstract `Controller`, part of the model-view-controller (MVC) 
foundation, offers some more involved functionality when used with the router.

The following simple controller example makes use of Darya's HTTP abstractions
and controller dispatcher.

```php
require_once "vendor/darya/framework/autoloader.php";

use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Http\Session;
use Darya\Mvc\Controller;
use Darya\Routing\Dispatcher;
use Darya\Routing\Router;

class MyController extends Controller {
	
	public function index() { // The default controller action
		return '<a href="new/foo">Make a new foo</a>';
	}
	
	public function view($thing = null) {
		return "Showing you the $thing!";
	}

	public function newAction($thing = null) {
		return "Made a new $thing!";
	}
	
}

$router = new Router(array(
	'/:action?/:thing?' => 'MyController'
));

$dispatcher = new Dispatcher($router);

$dispatcher->respond(Request::createFromGlobals(), new Response);
```