# Darya Framework Changelog

## v0.4.0 - Jan ?, 2015
- Implemented improved `Dispatcher` functionality in `Router`, making 
  `Dispatcher` redundant
- Symfony's `EventDispatcher` can be optionally used for routing hooks
- Implemented optional usage of service container for resolving route callables
- Method support for `Container::call`

## v0.4.0-dev - Jan 8, 2015
- More expressive routing API
- Implemented reverse routing (using named routes)
- Various non-backwards-compatible API changes, hence the minor version change

## v0.3.0 - Nov 26, 2014
- Readme files
- Autoloader script (`autoloader.php`)
- Improvements to `Autoloader`
- Minor and cosmetic improvements to `Request` and `Response` classes
- `Response` accepts arrays as content to be sent as JSON
- Implemented `Model::toArray`, `Model::toJson` and some useful interfaces
- Implemented `SmartyView`
- Routes accept objects as default controllers
- Respond to a request directly from a `Router` using `Router::respond`
- Implemented service container reflection so function or class constructor
  parameters can be resolved using `Container::call` or `Container::create` if
  the type-hinted classes or interfaces are registered with the container

## v0.2.0-dev - Oct 12, 2014
- Initial commit after a few months of experimentation