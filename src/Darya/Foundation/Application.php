<?php
namespace Darya\Foundation;

use Darya\Service\Application as BaseApplication;

class Application extends BaseApplication
{
	/**
	 * @var string
	 */
	protected $basePath;
	
	/**
	 * Instantiate a new Darya application.
	 * 
	 * @param string $basePath [optional]
	 * @param array  $services [optional]
	 */
	public function __construct($basePath = null, array $services = array())
	{
		$this->basePath($basePath);
		
		parent::__construct($services);
	}
	
	/**
	 * Retrieve and optionally set the base path of the application.
	 * 
	 * @param string $basePath [optional]
	 * @return string
	 */
	public function basePath($basePath = null)
	{
		if ($basePath) {
			$this->basePath = $basePath;
			$this->set('path', $this->basePath);
		}
		
		return $this->basePath;
	}
}
