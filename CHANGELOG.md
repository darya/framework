# Darya Framework Changelog

## v0.4.0 - Feb 25, 2015
- Minor `Autoloader` improvement
- Major `Router` refactoring using method extraction
- `Session` objects can now be accessed like arrays, and as a result through
  their parent `Request` objects in the same way as other request data.
- `Response` refactoring, also removed `Response::addContent()`
- Removed redundant `Tools::processPost()`
- Added `ContainerInterface::all()` and `SessionInterface::has()`
- `Container` now automatically injects itself to any `ContainerAwareInterface`
  implementors that it instantiates
- Simplified `Request`/`Response` API

## v0.4.0-beta - Jan 29, 2015
- `Autoloader` refactoring
- Implemented improved `Dispatcher` functionality in `Router`, making
  `Dispatcher` redundant
- Implemented `Events` component that can be optionally used for routing hooks
- Implemented optional usage of service container for resolving route
  controllers and actions
- Method support for `Container::call()`
- Implemented `Container::share()` to wrap callable service definitions in
  closures that always return the same instance. This enables lazy-loading
  service instances, only instantiating them when resolved from the container.
- Request objects parse URI queries into get variables and store a path value.
- Updated main readme to consist primarily of code examples.
- `Controller` now implements ContainerAwareInterface, as does `Router`.
- Added more thorough `Facade` exceptions
- Implemented `Application` class which registered and boots service providers.

## v0.4.0-alpha - Jan 8, 2015
- More expressive routing API
- Implemented reverse routing (using named routes)
- Various non-backwards-compatible API changes, hence the minor version change

## v0.3.0 - Nov 26, 2014
- Readme files
- Autoloader script (`autoloader.php`)
- Improvements to `Autoloader` class
- Minor and cosmetic improvements to `Request` and `Response` classes
- `Response` accepts arrays as content to be sent as JSON
- Implemented `Model::toArray()`, `Model::toJson()` and some useful interfaces
- Implemented `SmartyView`
- Routes accept objects as default controllers
- Respond to a request directly from a `Router` using `Router::respond`
- Implemented service container reflection so function or class constructor
  parameters can be resolved using `Container::call` or `Container::create` if
  the type-hinted classes or interfaces are registered with the container

## v0.2.0-dev - Oct 12, 2014
- Initial commit after a few months of experimentation