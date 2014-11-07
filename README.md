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

To get started you'll want to make use of a class autoloader to save you from having to individually include a file for every class you want to use.

#### Composer's autoloader
```php
require_once 'vendor/autoload.php';
```

#### Darya's autoloader

If you want to use Darya's autoloader for your own code, just include Darya's autoload script (which includes Composer's autoloader if it exists).

Please note that it is sensitive to the directory from which you include it.

```php
require_once 'vendor/darya/framework/autoloader.php';
```

You can configure the autoloader created by this script if desired. 

Use namespaces as keys and filesystem paths as values for the array you pass to this function.

```php
$autoloader = require_once 'vendor/darya/framework/autoloader.php';

$autoloader->registerNamespaces(array(
	'MyNamespace' => 'app',
	'MyNamespace' => 'app/MyNamespace', // This works as well as the previous
	'MyNamespace\MyClass' => 'app/MyNamespace/MyClass.php'
));
```

### Routing

Darya's router is the heart of the framework. You can use it to direct HTTP requests to different functions or class methods.

The router uses Darya's simple abstractions of HTTP requests and responses but you don't have to use them yourself.

#### Front controller

First, you'll want to set up a PHP script as a [front controller](http://en.wikipedia.org/wiki/Front_Controller_pattern). If you're using Apache as your web server you could achieve this with a `.htaccess` file at the root of your public directory. 

```
RewriteEngine on

# Redirect requests for any non-existing files to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule .* index.php [L,QSA]
```

#### Routing to functions

You can define routes when instantiating a router, where URL paths to match are 
keys and callables are values.

Here is an example of routing to anonymous functions.

```php
use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Routing\Router;

// Define some routes
$router = new Router(array(
	'/about' => function() {
		return new Response('About this app!');
	},
	'/:page/:subpage' => function($page, $subpage) {
		// Render some awesome $subpage of $page
	},
	'/:page' => function($page) {
		// Render some awesome $page
	},
	'/' => function(){
		return 'Hello world!';
	}
));

// Set an error handler for requests that don't match a route
$router->setErrorHandler(function(){
	$response = new Response('No route was matched!');
	$response->setStatus(404);
	return $response;
});

// Respond to the current request
$router->respond(Request::createFromGlobals());
```

When using the router's `respond` method, returning `Response` objects is 
optional.

You can also define your own `Request` objects or just pass a URL as a string.

```php
$router->respond(new Request('/about', 'post'));

$router->respond($_SERVER['REQUEST_URI']);
```

#### Routing to class methods

