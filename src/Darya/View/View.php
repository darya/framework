<?php
namespace Darya\View;

/**
 * Darya's interface for views.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
interface View
{
	/**
	 * Select a template and optionally assign arguments and configuration variables.
	 *
	 * @param string $file 	    The template file to be used
	 * @param array  $arguments [optional] Arguments to assign to the template
	 * @param array  $config    [optional] Configuration variables for the view
	 */
	public function select($file, array $arguments = [], array $config = []);

	/**
	 * Get and optionally set view configuration variables.
	 *
	 * This merges given variables with any that have been previously set.
	 *
	 * @param array $config [optional]
	 * @return array
	 */
	public function config(array $config = []);

	/**
	 * Assign an array of arguments to the template.
	 *
	 * @param array $arguments Arguments to assign to the template
	 */
	public function assign(array $arguments);

	/**
	 * Get all arguments or a specific argument assigned to the template.
	 *
	 * @param string $key [optional] The key of the argument to return
	 * @return mixed The value of the $key argument if set, all arguments otherwise
	 */
	public function assigned($key = null);

	/**
	 * Render the view.
	 *
	 * @return string The rendered view
	 */
	public function render();
}
