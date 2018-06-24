# Darya Framework Changelog

## v0.5.0 - Jun 24, 2018

### General
- Improved readme files for each component
- Cleaned up trailing whitespace and unused imports

### Database
- Implemented SQLite adapter
- Implemented SQL Server ANSI offset (#49)
- Fixed a bug when using `null` in array filter values
- Fixed use of deprecated `MYSQL_ASSOC` constant
- Implemented SQLite support for `Database\Factory`

### ORM
- Ensured that records loaded through find() and findOrNew() are reinstated (no
  changed attributes)
- Updated `Record::save()` to skip saving if no data has changed
- Implemented `Record::attach()` and `Record::detach()`, which are now used for
  magic setters on relation attributes, instead of saving (associating) them
  immediately (#46)
- Improved belongs-to consistency issues
- Added `JsonSerializable` interface to `Model`
- Updated `Record::call()` to be more strict; avoids unexpected behavior such
  as any method call on a `Record` being valid
- Implemented `Relation::query()` support - allows opening relation queries
  from a relation object
- Fixed an issue when associating has-many relations with empty ID values

### Storage
- Renamed `Queryable::execute()` and `Query\Builder::execute()` methods to `run()`
- Improved in-memory storage when updating and deleting
- Added @mixin Query annotation for `Storage\Query\Builder` (#53)

## Service
- Fixed strict standards issue with `Facade`
- Implemented delegate service containers


## v0.5.0-beta3 - Nov 19, 2016

### General
- Added readmes for all packages, apart from Foundation
- Simplified the framework readme
- PSR-2 code style for all packages
- PSR-4 namespacing for all unit tests

### Database
- Implemented a database-specific query object that provides table joins,
  subqueries, `GROUP BY` and `HAVING`, which database query translators now
  support
- Fixed MySQL connection query issues that occurred without the `mysqlnd`
  extension
- Added missing error check after preparing MySQL query statement result -
  Catches syntax or logical errors with queries, e.g. 'Subquery returns more
  than 1 row' for column subqueries

### Foundation
- Implemented a new `Foundation` namespace to house classes that aid the set up
  of an application, including a configuration interface with implementations
  and a handful of default service providers
- Moved the autoloader to this namespace

### HTTP
- Improved HTTP response
  - Changed response to prepare content as a string when *sent* instead of when
    the content is *set*
  - Changed content type to `application/json` instead of `text/json` for array
    content
  - Implemented `Response::body()` for retrieving content as a string
  - Removed old cookie methods
  - Refactored and added more dynamic properties such as `status`, `headers`,
    `cookies`, `content` and `redirected`
  - Unit tested

### ORM
- Lots of general improvements and refactoring for `Model` and `Record`
- Implemented querying parameters (filter, order, limit) to relations and eager
  loading in `Record`
- `Model::data()` now returns transformed (non-raw attributes)
  - Raw attribute access has moved to `Model::rawData()`
  - The `toArray()`, `toJson()` and `getIterator()` methods now utilise the
    transformed attributes
- Implemented `Model::convertToJson()` - allows converting plain array of models
  to JSON
- Added unique() method to `Query` objects and `Query\Builder` executor methods

## v0.5.0-beta2 - Feb 18, 2016

### ORM
- Implemented relation constraints
- Improved model hydration, generation and reinstatement
- Tested relations and records more thoroughly
- Implemented dot notation for `Record`s for accessing relation attributes
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
