<?php
namespace Darya\View;

/**
 * Darya's abstract view implementation.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class AbstractView implements View
{
	/**
	 * Optional shared base path for selecting template files.
	 *
	 * @var string
	 */
	protected static $basePath;

	/**
	 * Set of template file extensions compatible with this view.
	 *
	 * @var array
	 */
	protected static $extensions = [];

	/**
	 * Variables to assign to all templates.
	 *
	 * @var array
	 */
	protected static $shared = [];

	/**
	 * Shared resolver for selecting template files.
	 *
	 * @var Resolver
	 */
	protected static $sharedResolver;

	/**
	 * Instance resolver for selecting template files.
	 *
	 * @var Resolver
	 */
	protected $resolver;

	/**
	 * Variables for configuring the view.
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 * Path to the directory containing the view template.
	 *
	 * @var string
	 */
	protected $directory;

	/**
	 * Filename of the view template.
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * Variables to assign to the template.
	 *
	 * @var array
	 */
	protected $arguments = [];

	/**
	 * Set a shared base path for selecting template files.
	 *
	 * @param string $path
	 */
	public static function setBasePath($path)
	{
		$path = realpath($path);

		if (is_dir($path)) {
			static::$basePath = $path;
		}
	}

	/**
	 * Sets a resolver for all views.
	 *
	 * @param Resolver $resolver
	 */
	public static function setSharedResolver(Resolver $resolver)
	{
		static::$sharedResolver = $resolver;
	}

	/**
	 * Register template file extensions.
	 *
	 * @param string|array $extensions
	 */
	public static function registerExtensions($extensions)
	{
		$extensions = array_map(function ($extension) {
			return '.' . ltrim(trim($extension), '.');
		}, (array) $extensions);

		static::$extensions = array_merge(static::$extensions, $extensions);
	}

	/**
	 * Instantiate a new View object.
	 *
	 * @param string $file      [optional] Path to the template file to use
	 * @param array  $arguments [optional] Arguments to assign to the template
	 * @param array  $config    [optional] Configuration variables for the view
	 */
	public function __construct($file = null, $arguments = [], $config = [])
	{
		$this->select($file, $arguments, $config);
	}

	/**
	 * Evaluate the template as a string by rendering it.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Sets a resolver for this view.
	 *
	 * @param Resolver $resolver
	 */
	public function setResolver(Resolver $resolver)
	{
		$this->resolver = $resolver;
	}

	/**
	 * Select a template and optionally assign arguments and configuration variables.
	 *
	 * @param string $file      The template file to be used
	 * @param array  $arguments [optional] Arguments to assign to the template immediately
	 * @param array  $config    [optional] Config arguments for the view
	 */
	public function select($file, array $arguments = [], array $config = [])
	{
		if (!empty($config)) {
			$this->config($config);
		}

		if (!empty($arguments)) {
			$this->assign($arguments);
		}

		if ($file) {
			$this->file($file);
		}
	}

	/**
	 * Attempt to load a template file at the given absolute path.
	 *
	 * @param string $path Absolute file path
	 * @return bool
	 */
	protected function attempt($path)
	{
		$path = realpath($path);

		if ($path && is_file($path)) {
			$dirname = dirname($path);
			$this->directory($dirname);
			$this->file = basename($path);

			return true;
		}

		return false;
	}

	/**
	 * Attempt to resolve the given view path to a file path using the view's
	 * resolver or that class's shared resolver.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function resolve($path)
	{
		if ($this->resolver) {
			return $this->resolver->resolve($path);
		} else if (static::$sharedResolver) {
			return static::$sharedResolver->resolve($path);
		}

		return null;
	}

	/**
	 * Find and set a template file using a given path. Attempts with the shared
	 * base path and extensions.
	 *
	 * @param string $path Path to template file
	 * @return bool
	 */
	public function file($path)
	{
		$paths = [];

		$paths[] = $this->resolve($path);

		$extensions = array_merge(static::$extensions, ['']);

		foreach ($extensions as $extension) {
			if (static::$basePath) {
				$paths[] = static::$basePath . "/$path$extension";
			}

			$paths[] = "$path$extension";
		}

		foreach ($paths as $p) {
			if ($this->attempt($p)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get and optionally set the template's working directory.
	 *
	 * @param string $directory [optional] Working directory path
	 * @return string
	 */
	protected function directory($directory = null)
	{
		$this->directory = $directory != '.' ? $directory : '';

		return $this->directory;
	}

	/**
	 * Get and optionally set view configuration variables.
	 *
	 * This merges given variables with any that have been previously set.
	 *
	 * @param array $config [optional]
	 * @return array
	 */
	public function config(array $config = [])
	{
		$this->config = array_merge($this->config, $config);

		return $this->config;
	}

	/**
	 * Assign an array of arguments to the template.
	 *
	 * @param array $arguments
	 */
	public function assign(array $arguments = [])
	{
		$this->arguments = array_merge($this->arguments, $arguments);
	}

	/**
	 * Get all arguments or a particular argument assigned to the template.
	 *
	 * @param string $key [optional] The key of the argument to return
	 * @return mixed The value of the $key argument if set, all arguments otherwise
	 */
	public function assigned($key = null)
	{
		return !is_null($key) && isset($this->arguments[$key]) ? $this->arguments[$key] : $this->arguments;
	}

	/**
	 * Assign an array of arguments to all templates.
	 *
	 * @param array $arguments
	 */
	public static function share(array $arguments)
	{
		static::$shared = array_merge(static::$shared, $arguments);
	}

	/**
	 * Get all arguments or a particular argument shared with all templates.
	 *
	 * @param string $key The key of the argument to return
	 * @return mixed The value of the $key argument if set, all arguments otherwise
	 */
	public static function shared($key = null)
	{
		return !is_null($key) && isset(static::$shared[$key]) ? static::$shared[$key] : static::$shared;
	}
}
