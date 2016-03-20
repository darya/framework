# Darya Framework Changelog

## Unreleased
- Implemented a database-specific query object that provides table joins and
  subqueries (which database query translators now use).
- Implemented a new `Foundation` namespace to house classes that aid the set up
  of an application, including configuration and default service providers.
- Fixed a MySQL connection query bug that occured without the mysqlnd extension.
- Model::data() now returns transformed (non-raw attributes). Raw attribute
  access has moved to Model::rawData(). The toArray(), toJson() and
  getIterator() methods now utilise the transformed attributes.

## v0.5.0-beta2 - Feb 18, 2015

### ORM
- Implemented relation constraints
- Improved model hydration, generation and reinstatement
- Tested relations and records more thoroughly
- Implemented dot notation for `Records` for accessing relation attributes
- Refactored relation naming and factory method
  - Allows string keys in relation definitions, which call methods on the
    relation when it is built, such as `'foreignKey'`, `'localKey'` and
    `'constrain'`

### Database
- Refactored abstract SQL translator
- Fixed null and boolean comparisons for MySQL queries
- Refactored database/storage results
- Implemented iterable storage results (includes database results)
- Updated database connections to accept `Query` objects for their `query()`
  methods
- Database factory throws an exception if it fails, instead of returning null

### HTTP
- Fixed and refactored `Request` creation, path retrieval and optionally setting
  status code
- Implemented default value parameters for `Request` data retrieval methods
- Implemented `content()` method for `Request` objects


## v0.5.0-beta - Nov 4, 2015
- Updated MySQL connection to work without the mysqlnd driver
- Implemented `InMemory` storage for unit tests; includes in-memory filtering,
  sorting, limiting etc
- Implemented many-to-many eager loading and counting
- Refactored, tested and fixed ORM relations and other ORM & database classes

## v0.5.0-alpha2 - Sep 17, 2015
- Implemented SQL Server database connection
- Implemented prepared queries for MySQL connection
- Implemented dedicated `Query` class to represent database queries
- Implemented `Transformer` classes for `Model` attribute access and mutation
- Implemented fluent query builder with queryable storage and dedicated
  translator classes
- Implemented reverse routing, or route URL/path generation
- Added event dispatchment to database connections
- Extracted handling of `Response` cookies to a `Cookies` class
- Removed `escape()` methods from database connections.
- Many database-related bug fixes.

## v0.5.0-alpha - Jul 22, 2015
- Implemented `Database`, `ORM` and `Storage` packages
- Removed use of global `DEBUG` constant
- Made `Response` content optional
- Wrote lots of unit tests
- Removed `Interface` suffix for many interfaces
- Implemented recursive service/alias resolution for `Container`.
- Renamed abstract `View` class to `AbstractView`

## v0.4.0 - Feb 25, 2015
- Minor `Autoloader` improvement
- Major `Router` refactoring using method extraction
- `Session` objects can now be accessed like arrays, and as a result through
  their parent `Request` objects in the same way as other request data
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