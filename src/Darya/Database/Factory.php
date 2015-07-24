<?php
namespace Darya\Database;

/**
 * Darya's database connection factory.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Factory {
	
	/**
	 * @var class|string Class name or database name to use by default
	 */
	protected $default = 'mysql';
	
	/**
	 * @param class|string $default
	 */
	public function __construct($default = null) {
		$this->default = $default ?: $this->default;
	}
	
	/**
	 * Prepare an options array by ensuring the existence of expected keys.
	 * 
	 * @param array $options
	 * @return array
	 */
	protected function prepareOptions(array $options) {
		return array_merge(array(
			'host'     => 'localhost',
			'username' => null,
			'password' => null,
			'database' => null,
			'port'     => null
		), $options);
	}
	
	/**
	 * Resolve a class name from the given string.
	 * 
	 * @param string $string
	 * @return string
	 */
	protected function resolveClass($string) {
		if (class_exists($string)) {
			return $string;
		}
		
		$class = null;
		
		switch ($name) {
			case 'mysql':
				$class = 'Darya\Database\Connection\MySql';
				break;
			case 'mssql': case 'sqlserver':
				$class = 'Darya\Database\Connection\SqlServer';
				break;
		}
		
		return $class;
	}
	
	/**
	 * Create a new database connection using the given name/class and options.
	 * 
	 * @param class|string $name
	 * @param array $options Expects keys 'host', 'username', 'password', 'database' and optionally 'port'
	 * @return \Darya\Database\Connection
	 */
	public function create($name = null, array $options = array()) {
		$name = $name ?: $this->default;
		$class = $this->resolveClass($name);
		$options = $this->prepareOptions($options);
		
		if (class_exists($class)) {
			return new $class(
				$options['host'],
				$options['username'],
				$options['password'],
				$options['database'],
				$options['port']
			);
		}
		
		return null;
	}
	
}