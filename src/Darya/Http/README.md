# `Darya\Http`

Darya provides some simple abstractions for HTTP requests and responses, as well
as session control.

- [Requests](#requests)
  - [Creating requests](#creating-requests)
  - [Reading request data](#accessing-request-data)
    - [Retrieving the request URI](#retrieving-the-request-uri)
    - [Determining the request method](#determining-the-request-method)
    - [Retrieving the request body](#retrieving-the-request-body)
    - [Retrieving parameters values and other data](#retrieving-parameter-values-and-other-data)
    - [Testing for an ajax request](#testing-for-an-ajax-request)
- [Responses](#responses)
  - [Creating responses](#creating-responses)
  - [Headers](#setting-headers)
  - [Content](#content)
  - [Redirecting](#redirecting)
  - [Sending](#sending)
- [Sessions](#sessions)

## Requests

Request objects can be created with just a URI.

An HTTP method can optionally be supplied (`GET`, `POST`, 'PUT, etc), with `GET`
being the default.

Request methods are treated case insensitively.

### Creating requests

```php
use Darya\Http\Request;

$request = Request::create('/hello');
```

They can be populated with request data when instantiated. This data is expected
to mirror the structure of PHP's superglobals, which means the superglobals
themselves can be used to build a representation of the current request.

```php
$request = Request::create($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], array(
	'get'    => $_GET,
	'post'   => $_POST,
	'cookie' => $_COOKIE,
	'file'   => $_FILES,
	'server' => $_SERVER,
	'header' => Request::headersFromGlobals($_SERVER)
))
```

This shortcut method does all of the above for you.

```php
$request = Request::createFromGlobals();
```

### Reading request data

Assume the request URI `/hello?id=10` for the following examples.

#### Retrieving the request URI

```php
// GET /hello?id=10
$request->uri(); // '/hello'
```
#### Determining the request method

```php
$request->method();       // 'get'
$request->method('get');  // true
$request->method('post'); // false
```

#### Retrieving the request body

```php
$body = $request->body(); // '{"data":{"my":"payload"}}'
```

#### Retrieving parameter values and other data

```php
$request->get('id');  // 10
$request->post('id'); // null
$request->any('id');  // 10 (checks post then get)

$request->cookie('my_cookie'); // Accessing cookies

// IP of the client that issued the current request
$request->server('REMOTE_ADDR');

$request->ip(); // Same as the above
```

#### Testing for an ajax request

```php
$request->header('X-Requested-With') === 'XmlHttpRequest';

$request->ajax(); // Similar to above but also checks for 'ajax' get and post parameters
```

## Responses

Response objects determine the response sent back to the browser of the client
accessing your application.

### Creating responses

Responses can be empty.

```php
use Darya\Http\Response;

$response = new Response;
```

They can be optionally instantiated with content and headers up front.

```php
$response = new Response('Hello, world!');
```

```php
$reponse = new Response('This response has a special header.', [
	'My-header: My header value'
]);
```

### Headers

Headers can be set one at a time or all at once.

```php
// Set a single header
$response->header('Content-Type: application/json');

// Set many headers
$response->headers([
	'Content-Type: application/json',
	'My-header: My header value'
]);
```

### Content

Content can be set to a string, or anything can be cast to a string.

```php
// Set the content to be a string
$response->content('Hello, world!');

// Set the content to be an object that can be cast to a string
$response->content($object);
```

Content can be cleared by setting it to `false`.

```php
$response->content(false);
```

Setting the content as an array automatically sets the
`Content-Type: application/json` header. When the response is sent, the array
will be serialized as JSON.

```php
// Sent as {"hello":"world"}
$response->content([
	'hello' => 'world'
]);
```

To retrieve the serialized response content before it is sent, use the `body()`
method.

```php
$response->content([
	'hello' => 'world'
]);

$body = $response->body(); // '{"hello":"world"}'
```

### Redirecting

Redirect clients to another URL using the `redirect()` method.

```php
$response->redirect('https://google.co.uk/');
```

This sets the `Location` header and flags the response as redirected.

### Sending

Send the response with the `send()` method.

```php
$response->send();
```

If you need to send the headers and body separately, use `sendHeaders()` and
`sendContent()`. It is recommended to use only the send() method, however.

`sendHeaders()` will not send headers if they have already been sent, either by
this request or elsewhere in PHP.

## Sessions

_To be written._
