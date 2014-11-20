<?php
namespace Darya\Database;

/**
 * Darya's database connection factory.
 * 
 * @author Chris Andrew
 */
class Factory {
	
	/**
	 * @var class|string Class name or database name to use by default
	 */
	protected $default;
	
	/**
	 * @param class|string $default
	 */
	public function __construct($default = null) {
		$this->default = $default;
	}
	
	/**
	 * Create a new database connection using the given name/class and options.
	 * 
	 * @param class|string $name
	 * @param array $options Expects keys 'host', 'username', 'password' and 'database'
	 * @return Darya\Database\DatabaseInterface
	 */
	public function create($name = null, $options = array()) {
		$class = null;
		
		if (class_exists($name)) {
			$class = $name;
		}
		
		if (!$class) {
			switch ($name) {
				case 'mysql':
					$class = 'Darya\Database\MySQLi'; 
					break;
				default:
					$class = $this->default;
			}
		}
		
		if (class_exists($class)) {
			return new $class(@$options['host'], @$options['username'], @$options['password'], @$options['database']);
		}
		
		return null;
	}
	
}