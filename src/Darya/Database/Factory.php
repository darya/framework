<?php
namespace Darya\Database;

use Darya\Database\Connection;
use UnexpectedValueException;

/**
 * Darya's database connection factory.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Factory
{
	/**
	 * The class name or database type name to use by default
	 *
	 * @var string
	 */
	protected $default = 'mysql';

	/**
	 * A map of database type names to connection implementation classes
	 *
	 * @var array
	 */
	protected $map = array(
		'mysql'     => 'Darya\Database\Connection\MySql',
		'mssql'     => 'Darya\Database\Connection\SqlServer',
		'sqlserver' => 'Darya\Database\Connection\SqlServer',
		'sqlite'    => 'Darya\Database\Connection\Sqlite'
	);

	/**
	 * Instantiate a new database factory.
	 *
	 * @param string $default [optional] The default database type name
	 */
	public function __construct($default = null)
	{
		$this->default = $default ?: $this->default;
	}

	/**
	 * Prepare an options array by ensuring the existence of expected keys.
	 *
	 * @param array $options
	 * @return array
	 */
	protected function prepareOptions(array $options)
	{
		return array_merge(array(
			'hostname' => 'localhost',
			'username' => null,
			'password' => null,
			'database' => null,
			'port'     => null,
			'options'  => array()
		), array_filter($options));
	}

	/**
	 * Resolve a database connection class name from the given string.
	 *
	 * @param string $string
	 * @return string
	 */
	protected function resolveClass($string)
	{
		if (class_exists($string)) {
			return $string;
		}

		$string = strtolower($string);

		if (!isset($this->map[$string])) {
			return null;
		}

		return $this->map[$string];
	}

	/**
	 * Create a new database connection using the given name/class and options.
	 *
	 * @param string $name    The database type name
	 * @param array  $options Expects keys 'hostname', 'username', 'password',
	 *                        'database' and optionally 'port'
	 * @return Connection
	 */
	public function create($name = null, array $options = array())
	{
		$name = $name ?: $this->default;
		$class = $this->resolveClass($name);
		$options = $this->prepareOptions($options);

		if (!class_exists($class) || !is_subclass_of($class, 'Darya\Database\Connection')) {
			throw new UnexpectedValueException(
				"Couldn't resolve a valid database connection instance for type '$name'"
			);
		}

		// TODO: Clean this up
		if ($class === 'Darya\Database\Connection\Sqlite') {
			return new $class($options['database'], $options['options']);
		}

		return new $class(
			$options['hostname'],
			$options['username'],
			$options['password'],
			$options['database'],
			$options['port']
		);
	}
}
